<?php

namespace BlueFission\Data\Schema;

use BlueFission\Obj;
use BlueFission\DataTypes;
use BlueFission\Val;
use BlueFission\DevElation as Dev;

class FieldDefinition extends Obj
{
    protected $_data = [
        'name' => '',
        'type' => '',
        'required' => false,
        'nullable' => false,
        'default' => null,
        'hasDefault' => false,
        'cast' => true,
        'constraints' => [],
        'items' => null,
        'schema' => null,
        'source' => '',
    ];

    protected $_types = [
        'name' => DataTypes::STRING,
        'type' => DataTypes::STRING,
        'required' => DataTypes::BOOLEAN,
        'nullable' => DataTypes::BOOLEAN,
        'cast' => DataTypes::BOOLEAN,
        'constraints' => DataTypes::ARRAY,
        'source' => DataTypes::STRING,
        'hasDefault' => DataTypes::BOOLEAN,
    ];

    public function __construct(string $name, array $config = [])
    {
        parent::__construct();

        $config = Dev::apply('_in', $config);
        $type = $config['type'] ?? '';
        if ($type instanceof DataTypes) {
            $type = $type->value;
        }

        $constraints = $config['constraints'] ?? [];
        if (Val::isNotNull($constraints) && !is_array($constraints)) {
            $constraints = [$constraints];
        }

        $hasDefault = array_key_exists('default', $config);

        $this->assign([
            'name' => $name,
            'type' => is_string($type) ? $type : '',
            'required' => (bool)($config['required'] ?? false),
            'nullable' => (bool)($config['nullable'] ?? false),
            'default' => $config['default'] ?? null,
            'hasDefault' => $hasDefault,
            'cast' => (bool)($config['cast'] ?? true),
            'constraints' => $constraints ?? [],
            'items' => $config['items'] ?? null,
            'schema' => $config['schema'] ?? ($config['fields'] ?? null),
            'source' => (string)($config['source'] ?? ''),
        ]);

        Dev::do('_after', [$this]);
    }

    public function name(): string
    {
        return (string)$this->field('name');
    }

    public function type(): string
    {
        return (string)$this->field('type');
    }

    public function required(): bool
    {
        return (bool)$this->field('required');
    }

    public function nullable(): bool
    {
        return (bool)$this->field('nullable');
    }

    public function cast(): bool
    {
        return (bool)$this->field('cast');
    }

    public function hasDefault(): bool
    {
        return (bool)$this->field('hasDefault');
    }

    public function defaultValue()
    {
        return $this->field('default');
    }

    public function constraints(): array
    {
        $value = $this->field('constraints');
        return is_array($value) ? $value : [];
    }

    public function items()
    {
        return $this->field('items');
    }

    public function schema()
    {
        return $this->field('schema');
    }

    public function source(): string
    {
        return (string)$this->field('source');
    }
}
