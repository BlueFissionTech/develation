<?php

namespace BlueFission\Cli\Args;

use BlueFission\Obj;
use BlueFission\DataTypes;
use BlueFission\Val;
use BlueFission\DevElation as Dev;

class OptionDefinition extends Obj
{
    protected $_data = [
        'name' => '',
        'short' => [],
        'type' => 'string',
        'default' => null,
        'required' => false,
        'repeatable' => false,
        'env' => '',
        'description' => '',
        'aliases' => [],
    ];

    protected $_types = [
        'name' => DataTypes::STRING,
        'short' => DataTypes::ARRAY,
        'type' => DataTypes::STRING,
        'required' => DataTypes::BOOLEAN,
        'repeatable' => DataTypes::BOOLEAN,
        'env' => DataTypes::STRING,
        'description' => DataTypes::STRING,
        'aliases' => DataTypes::ARRAY,
    ];

    public function __construct(string $name, array $config = [])
    {
        parent::__construct();

        $name = Dev::apply('_in', $name);
        $config = Dev::apply('_in', $config);

        $short = $config['short'] ?? [];
        if (Val::isNotNull($short) && !is_array($short)) {
            $short = [$short];
        }

        $aliases = $config['aliases'] ?? [];
        if (Val::isNotNull($aliases) && !is_array($aliases)) {
            $aliases = [$aliases];
        }

        $this->assign([
            'name' => $name,
            'short' => $short ?? [],
            'type' => $config['type'] ?? 'string',
            'default' => $config['default'] ?? null,
            'required' => (bool)($config['required'] ?? false),
            'repeatable' => (bool)($config['repeatable'] ?? false),
            'env' => (string)($config['env'] ?? ''),
            'description' => (string)($config['description'] ?? ''),
            'aliases' => $aliases ?? [],
        ]);

        Dev::do('_after', [$this]);
    }

    public function name(): string
    {
        return (string)$this->field('name');
    }

    public function short(): array
    {
        $value = $this->field('short');
        return is_array($value) ? $value : [];
    }

    public function type(): string
    {
        return (string)$this->field('type');
    }

    public function defaultValue()
    {
        return $this->field('default');
    }

    public function required(): bool
    {
        return (bool)$this->field('required');
    }

    public function repeatable(): bool
    {
        return (bool)$this->field('repeatable');
    }

    public function env(): string
    {
        return (string)$this->field('env');
    }

    public function description(): string
    {
        return (string)$this->field('description');
    }

    public function aliases(): array
    {
        $value = $this->field('aliases');
        return is_array($value) ? $value : [];
    }
}
