<?php

namespace BlueFission\Cli\Args;

use BlueFission\Arr;
use BlueFission\Flag;
use BlueFission\Obj;
use BlueFission\Str;
use BlueFission\DataTypes;
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
        $config = Arr::make($config);

        $short = $config->hasKey('short') ? $config['short'] : [];
        if (!Arr::is($short)) {
            $short = [$short];
        }

        $aliases = $config->hasKey('aliases') ? $config['aliases'] : [];
        if (!Arr::is($aliases)) {
            $aliases = [$aliases];
        }

        $this->assign([
            'name' => Str::make($name)->trim()->val(),
            'short' => $short ?? [],
            'type' => $config->hasKey('type') ? (string)$config['type'] : 'string',
            'default' => $config->hasKey('default') ? $config['default'] : null,
            'required' => Flag::parseBool($config->hasKey('required') ? $config['required'] : false),
            'repeatable' => Flag::parseBool($config->hasKey('repeatable') ? $config['repeatable'] : false),
            'env' => $config->hasKey('env') ? (string)$config['env'] : '',
            'description' => $config->hasKey('description') ? (string)$config['description'] : '',
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
        return Arr::is($value) ? $value : [];
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
        return Arr::is($value) ? $value : [];
    }
}
