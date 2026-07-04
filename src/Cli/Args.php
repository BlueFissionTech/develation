<?php

namespace BlueFission\Cli;

use BlueFission\Obj;
use BlueFission\Arr;
use BlueFission\Flag;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Cli\Args\OptionDefinition;
use BlueFission\DevElation as Dev;

class Args extends Obj
{
    protected $_data = [
        'definitions' => [],
        'options' => [],
        'positionals' => [],
        'unknown' => [],
        'allowUnknown' => true,
        'autoHelp' => true,
        'command' => '',
    ];

    protected $_types = [
        'definitions' => DataTypes::ARRAY,
        'options' => DataTypes::ARRAY,
        'positionals' => DataTypes::ARRAY,
        'unknown' => DataTypes::ARRAY,
        'allowUnknown' => DataTypes::BOOLEAN,
        'autoHelp' => DataTypes::BOOLEAN,
        'command' => DataTypes::STRING,
    ];

    public function __construct(array $config = [])
    {
        parent::__construct();

        $config = Arr::make(Dev::apply('_in', $config));

        $this->assign([
            'allowUnknown' => $config->hasKey('allowUnknown') ? Flag::parseBool($config['allowUnknown'], true) : true,
            'autoHelp' => $config->hasKey('autoHelp') ? Flag::parseBool($config['autoHelp'], true) : true,
        ]);

        if ($this->field('autoHelp')) {
            $this->addOption(new OptionDefinition('help', [
                'short' => ['h'],
                'type' => 'bool',
                'description' => 'Show help and exit.',
            ]));
        }
    }

    public function addOption(OptionDefinition $definition): self
    {
        $definitions = Arr::make($this->arrayValue($this->field('definitions')));
        $definitions[$definition->name()] = $definition;
        $this->field('definitions', $definitions->val());

        return $this;
    }

    public function addOptions(array $definitions): self
    {
        Arr::make($definitions)->each(function ($definition) {
            if ($definition instanceof OptionDefinition) {
                $this->addOption($definition);
            }
        });

        return $this;
    }

    public function parse(array $argv): self
    {
        $argv = Dev::apply('_in', $argv);
        Dev::do('_before', [$argv, $this]);
        $this->perform(new Action(Action::PROCESS), new Meta(data: $argv));

        $options = [];
        $positionals = Arr::make();
        $unknown = Arr::make();
        $definitions = $this->arrayValue($this->field('definitions'));
        $map = $this->buildOptionMap($definitions);

        if (Arr::count($argv) > 0) {
            $this->field('command', (string)array_shift($argv));
        }

        $index = 0;
        $count = Arr::count($argv);
        while ($index < $count) {
            $arg = $argv[$index];
            if ($arg === '--') {
                $positionals->merge(array_slice($argv, $index + 1));
                break;
            }

            if (Str::pos($arg, '--') === 0) {
                $parsed = $this->parseLongOption($arg, $argv, $index, $map);
                if ($parsed !== null) {
                    [$name, $value] = $parsed;
                    if ($name === '') {
                        $unknown->push($arg);
                    } else {
                        $this->assignOption($options, $definitions[$name], $value);
                    }
                }
                $index++;
                continue;
            }

            if (Str::pos($arg, '-') === 0 && $arg !== '-') {
                $parsed = $this->parseShortOption($arg, $argv, $index, $map);
                if ($parsed !== null) {
                    foreach ($parsed as $entry) {
                        [$name, $value, $raw] = $entry;
                        if ($name === '') {
                            $unknown->push($raw);
                        } else {
                            $this->assignOption($options, $definitions[$name], $value);
                        }
                    }
                }
                $index++;
                continue;
            }

            $positionals->push($arg);
            $index++;
        }

        $this->applyDefaults($options, $definitions);
        $this->applyEnvFallbacks($options, $definitions);
        $this->validateRequired($options, $definitions);

        if (!$this->field('allowUnknown') && $unknown->isNotEmpty()) {
            throw new \RuntimeException('Unknown arguments: ' . $unknown->join(', ')->val());
        }

        $options = Dev::apply('_out', $options);
        $positionals = Dev::apply('_out', $positionals->val());
        $unknown = Dev::apply('_out', $unknown->val());

        $this->field('options', $options);
        $this->field('positionals', $positionals);
        $this->field('unknown', $unknown);

        $this->trigger(Event::PROCESSED, new Meta(data: [
            'options' => $options,
            'positionals' => $positionals,
            'unknown' => $unknown,
        ]));

        Dev::do('_after', [$this]);

        return $this;
    }

    public function options(): array
    {
        return $this->arrayValue($this->field('options'));
    }

    public function positionals(): array
    {
        return $this->arrayValue($this->field('positionals'));
    }

    public function unknown(): array
    {
        return $this->arrayValue($this->field('unknown'));
    }

    public function usage(?string $command = null): string
    {
        $definitions = $this->arrayValue($this->field('definitions'));
        $commandName = $command ?: $this->field('command');
        $commandName = $commandName ?: 'script.php';

        $lines = Arr::make();
        $lines->push('Usage: ' . $commandName . ' [options] [args]');
        $lines->push('');
        $lines->push('Options:');

        $rows = Arr::make();
        $maxWidth = 0;
        foreach ($definitions as $definition) {
            $flags = $this->formatFlags($definition);
            $rows->push([$flags, $definition->description(), $definition->defaultValue()]);
            $maxWidth = max($maxWidth, Str::len($flags));
        }

        foreach ($rows as $row) {
            $flags = str_pad($row[0], $maxWidth + 2, ' ', STR_PAD_RIGHT);
            $description = $row[1];
            $default = $row[2];
            if (Val::isNotNull($default) && $default !== '') {
                $description .= ' (default: ' . $default . ')';
            }
            $lines->push('  ' . $flags . $description);
        }

        $output = $lines->join(PHP_EOL)->val();
        return Dev::apply('_out', $output);
    }

    protected function parseLongOption(string $arg, array $argv, int &$index, array $map): ?array
    {
        $value = null;
        $name = '';
        $raw = $arg;

        $eqPos = Str::pos($arg, '=');
        if ($eqPos !== false) {
            $name = substr($arg, 2, $eqPos - 2);
            $value = substr($arg, $eqPos + 1);
        } else {
            $name = substr($arg, 2);
        }

        if (Str::pos($name, 'no-') === 0) {
            $candidate = substr($name, 3);
            if (isset($map['long'][$candidate])) {
                return [$map['long'][$candidate]->name(), false];
            }
        }

        $definition = $map['long'][$name] ?? null;
        if (!$definition) {
            return ['', null];
        }

        if ($this->requiresValue($definition) && $value === null) {
            $next = $argv[$index + 1] ?? null;
            if ($next !== null && Str::pos($next, '-') !== 0) {
                $value = $next;
                $index++;
            }
        }

        if (!$this->requiresValue($definition) && $value === null) {
            $value = true;
        }

        return [$definition->name(), $value];
    }

    protected function parseShortOption(string $arg, array $argv, int &$index, array $map): ?array
    {
        $chunk = substr($arg, 1);
        $results = [];
        $letters = Str::make($chunk)->splitBy('//', -1, PREG_SPLIT_NO_EMPTY)->val();

        if (Arr::count($letters) === 1) {
            $letter = $letters[0];
            $definition = $map['short'][$letter] ?? null;
            if (!$definition) {
                $results[] = ['', null, $arg];
                return $results;
            }

            $value = null;
            if ($this->requiresValue($definition)) {
                $next = $argv[$index + 1] ?? null;
                if ($next !== null && Str::pos($next, '-') !== 0) {
                    $value = $next;
                    $index++;
                }
            } else {
                $value = true;
            }
            $results[] = [$definition->name(), $value, $arg];
            return $results;
        }

        $allBooleans = true;
        foreach ($letters as $letter) {
            $definition = $map['short'][$letter] ?? null;
            if (!$definition || $this->requiresValue($definition)) {
                $allBooleans = false;
                break;
            }
        }

        if ($allBooleans) {
            foreach ($letters as $letter) {
                $definition = $map['short'][$letter];
                $results[] = [$definition->name(), true, '-' . $letter];
            }
            return $results;
        }

        $letter = array_shift($letters);
        $definition = $map['short'][$letter] ?? null;
        if (!$definition) {
            $results[] = ['', null, $arg];
            return $results;
        }

        $value = implode('', $letters);
        if ($value === '' && $this->requiresValue($definition)) {
            $next = $argv[$index + 1] ?? null;
            if ($next !== null && Str::pos($next, '-') !== 0) {
                $value = $next;
                $index++;
            }
        }

        if (!$this->requiresValue($definition) && $value === '') {
            $value = true;
        }

        $results[] = [$definition->name(), $value, $arg];
        return $results;
    }

    protected function buildOptionMap(array $definitions): array
    {
        $map = [
            'long' => [],
            'short' => [],
        ];

        foreach ($definitions as $definition) {
            $map['long'][$definition->name()] = $definition;
            foreach ($definition->aliases() as $alias) {
                $map['long'][$alias] = $definition;
            }
            foreach ($definition->short() as $short) {
                $map['short'][$short] = $definition;
            }
        }

        return $map;
    }

    protected function arrayValue($value): array
    {
        if (Arr::is($value)) {
            return $value;
        }

        if ($value instanceof Arr) {
            return $value->val();
        }

        return [];
    }

    protected function assignOption(array &$options, OptionDefinition $definition, $value): void
    {
        $name = $definition->name();
        $cast = $this->castValue($definition->type(), $value);

        if ($definition->repeatable() || $definition->type() === 'array') {
            $existing = $options[$name] ?? [];
            if (!Arr::is($existing)) {
                $existing = [$existing];
            }

            if (Arr::is($cast)) {
                $existing = Arr::make($existing)->merge($cast)->val();
            } else {
                $existing = Arr::make($existing)->push($cast)->val();
            }

            $options[$name] = $existing;
        } else {
            $options[$name] = $cast;
        }
    }

    protected function castValue(string $type, $value)
    {
        if ($type === 'bool') {
            return Flag::parseBool($value);
        }

        if ($type === 'int') {
            return (int)$value;
        }

        if ($type === 'float') {
            return (float)$value;
        }

        if ($type === 'array') {
            if (Arr::is($value)) {
                return $value;
            }
            $stringValue = (string)$value;
            if (Str::pos($stringValue, ',') !== false) {
                return Str::make($stringValue)
                    ->split(',')
                    ->map(function ($item) {
                        return Str::make((string)$item)->trim()->val();
                    })
                    ->val();
            }
            return [$stringValue];
        }

        return (string)$value;
    }

    protected function requiresValue(OptionDefinition $definition): bool
    {
        return $definition->type() !== 'bool';
    }

    protected function applyDefaults(array &$options, array $definitions): void
    {
        foreach ($definitions as $definition) {
            $name = $definition->name();
            if (!Arr::hasKey($options, $name) && Val::isNotNull($definition->defaultValue())) {
                $options[$name] = $definition->defaultValue();
            }
        }
    }

    protected function applyEnvFallbacks(array &$options, array $definitions): void
    {
        foreach ($definitions as $definition) {
            $name = $definition->name();
            if (Arr::hasKey($options, $name)) {
                continue;
            }

            $envKey = $definition->env();
            if ($envKey === '') {
                continue;
            }

            $envValue = getenv($envKey);
            if ($envValue === false) {
                continue;
            }

            $options[$name] = $this->castValue($definition->type(), $envValue);
        }
    }

    protected function validateRequired(array $options, array $definitions): void
    {
        foreach ($definitions as $definition) {
            if ($definition->required() && !Arr::hasKey($options, $definition->name())) {
                throw new \RuntimeException('Missing required option: ' . $definition->name());
            }
        }
    }

    protected function formatFlags(OptionDefinition $definition): string
    {
        $parts = Arr::make();
        foreach ($definition->short() as $short) {
            $parts->push('-' . $short);
        }
        $parts->push('--' . $definition->name());
        foreach ($definition->aliases() as $alias) {
            $parts->push('--' . $alias);
        }

        $suffix = '';
        if ($definition->type() !== 'bool') {
            $suffix = ' <' . $definition->type() . '>';
        }

        return $parts->join(', ')->append($suffix)->val();
    }
}
