<?php

namespace BlueFission\Data;

use BlueFission\Arr;
use BlueFission\IVal;
use BlueFission\Obj;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\ValFactory;
use BlueFission\Data\Schema\FieldDefinition;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Schema extends Obj
{
    protected $_data = [
        'fields' => [],
        'strict' => false,
        'errors' => [],
        'cast' => true,
    ];

    protected $_types = [
        'fields' => DataTypes::ARRAY,
        'strict' => DataTypes::BOOLEAN,
        'errors' => DataTypes::ARRAY,
        'cast' => DataTypes::BOOLEAN,
    ];

    public function __construct(array $fields = [], array $config = [])
    {
        parent::__construct();

        $config = Dev::apply('_in', $config);
        if (Arr::isAssoc($config)) {
            if (array_key_exists('strict', $config)) {
                $this->setValue('strict', (bool)$config['strict']);
            }
            if (array_key_exists('cast', $config)) {
                $this->setValue('cast', (bool)$config['cast']);
            }
        }

        if (!empty($fields)) {
            $this->defineMany($fields);
        }

        Dev::do('_after', [$this]);
    }

    public static function from(array $fields, array $config = []): self
    {
        return new self($fields, $config);
    }

    public function define(string $name, $definition): self
    {
        $definition = $this->normalizeDefinition($name, $definition);
        $fields = $this->arrayValue($this->field('fields'));
        $fields[$name] = $definition;
        $this->setValue('fields', $fields);
        $this->trigger(Event::CHANGE, new Meta(data: ['field' => $name]));

        return $this;
    }

    public function defineMany(array $fields): self
    {
        foreach ($fields as $name => $definition) {
            if (is_string($name)) {
                $this->define($name, $definition);
            } elseif ($definition instanceof FieldDefinition) {
                $this->define($definition->name(), $definition);
            }
        }

        return $this;
    }

    public function fields(): array
    {
        return $this->arrayValue($this->field('fields'));
    }

    public function fieldDefinition(string $name): ?FieldDefinition
    {
        $fields = $this->fields();
        if (!array_key_exists($name, $fields)) {
            return null;
        }

        return $this->normalizeDefinition($name, $fields[$name]);
    }

    public function hasField(string $name): bool
    {
        $fields = $this->fields();
        return array_key_exists($name, $fields);
    }

    public function requiredFields(): array
    {
        $names = [];
        foreach ($this->fields() as $name => $definition) {
            $definition = $this->normalizeDefinition((string)$name, $definition);
            if ($definition->required()) {
                $names[] = $definition->name();
            }
        }

        return $names;
    }

    public function optionalFields(): array
    {
        $names = [];
        foreach ($this->fields() as $name => $definition) {
            $definition = $this->normalizeDefinition((string)$name, $definition);
            if (!$definition->required()) {
                $names[] = $definition->name();
            }
        }

        return $names;
    }

    public function strict(?bool $value = null): bool
    {
        if (Val::isNull($value)) {
            return (bool)$this->field('strict');
        }

        $this->setValue('strict', (bool)$value);
        return (bool)$this->field('strict');
    }

    public function castSetting(?bool $value = null): bool
    {
        if (Val::isNull($value)) {
            return (bool)$this->field('cast');
        }

        $this->setValue('cast', (bool)$value);
        return (bool)$this->field('cast');
    }

    public function apply($data, ?bool $strict = null, ?bool $cast = null): array
    {
        $data = Dev::apply('_in', $data);
        Dev::do('_before', [$data, $this]);

        $this->perform(State::PROCESSING);
        $this->perform(new Action(Action::PROCESS), new Meta(data: $data));

        $strict = $strict ?? $this->strict();
        $cast = $cast ?? $this->castSetting();

        [$result, $errors] = $this->process($data, $strict, $cast);

        $this->setValue('errors', $errors);

        if (!empty($errors)) {
            $this->perform(Event::FAILURE, new Meta(data: $errors));
        } else {
            $this->perform(Event::SUCCESS, new Meta(data: $result));
        }

        $this->perform(Event::PROCESSED, new Meta(data: $result));

        $result = Dev::apply('_out', $result);
        Dev::do('_after', [$result, $this]);

        return $result;
    }

    public function validate($data, ?bool $strict = null): bool
    {
        $this->apply($data, $strict, false);
        return empty($this->errors());
    }

    public function transform($data, ?bool $strict = null): array
    {
        return $this->apply($data, $strict, true);
    }

    public function errors(): array
    {
        return $this->arrayValue($this->field('errors'));
    }

    public function errorsFor(string $field): array
    {
        $errors = $this->errors();
        return $errors[$field] ?? [];
    }

    public function clearErrors(): self
    {
        $this->setValue('errors', []);
        return $this;
    }

    protected function process($data, bool $strict, bool $cast): array
    {
        $input = $this->normalizeData($data);
        $definitions = $this->fields();
        $result = [];
        $errors = [];
        $usedKeys = [];
        $knownKeys = [];

        foreach ($definitions as $name => $definition) {
            $definition = $this->normalizeDefinition((string)$name, $definition);
            $source = $definition->source() !== '' ? $definition->source() : $definition->name();
            $knownKeys[] = $source;

            $hasKey = array_key_exists($source, $input);
            if (!$hasKey) {
                if ($definition->hasDefault()) {
                    $value = $this->resolveDefault($definition);
                    $hasKey = true;
                } elseif ($definition->required()) {
                    $this->addError($errors, $definition->name(), 'required');
                    continue;
                } else {
                    continue;
                }
            } else {
                $value = $input[$source];
                $usedKeys[] = $source;
            }

            if ($value === null) {
                if ($definition->nullable()) {
                    $result[$definition->name()] = null;
                    continue;
                }
                $this->addError($errors, $definition->name(), 'null_not_allowed');
                continue;
            }

            $processed = $this->processValue($definition->name(), $definition, $value, $cast, $strict, $input, $errors);
            if ($processed['ok']) {
                $result[$definition->name()] = $processed['value'];
            }
        }

        if ($strict) {
            foreach ($input as $key => $value) {
                if (!in_array($key, $knownKeys, true)) {
                    $this->addError($errors, (string)$key, 'unknown_field');
                }
            }
        } else {
            foreach ($input as $key => $value) {
                if (!in_array($key, $knownKeys, true)) {
                    $result[$key] = $value;
                }
            }
        }

        return [$result, $errors];
    }

    protected function processValue(string $name, FieldDefinition $definition, $value, bool $cast, bool $strict, array $input, array &$errors): array
    {
        $schema = $this->normalizeSchema($definition->schema());
        $items = $definition->items();
        $type = $this->normalizeType($definition->type());
        $useCast = $definition->cast() && $cast;

        if ($schema instanceof self) {
            if (!is_array($value) && !is_object($value)) {
                $this->addError($errors, $name, 'expected_object');
                return ['ok' => false, 'value' => null];
            }
            $nested = $schema->apply($value, $strict, $cast);
            $nestedErrors = $schema->errors();
            if (!empty($nestedErrors)) {
                $this->addError($errors, $name, 'schema_failed', $nestedErrors);
                return ['ok' => false, 'value' => null];
            }
            return ['ok' => true, 'value' => $nested];
        }

        if ($items !== null) {
            if (!is_array($value)) {
                if ($useCast) {
                    $value = Arr::make($value)->val();
                } else {
                    $this->addError($errors, $name, 'expected_array');
                    return ['ok' => false, 'value' => null];
                }
            }

            $itemDefinition = $this->normalizeItemDefinition($items);
            if ($itemDefinition) {
                $itemErrors = [];
                $processed = [];
                foreach ($value as $index => $itemValue) {
                    if ($itemValue === null && $itemDefinition->nullable()) {
                        $processed[$index] = null;
                        continue;
                    }

                    $itemResult = $this->processValue($name . '[' . $index . ']', $itemDefinition, $itemValue, $cast, $strict, $input, $itemErrors);
                    if ($itemResult['ok']) {
                        $processed[$index] = $itemResult['value'];
                    }
                }

                if (!empty($itemErrors)) {
                    $this->addError($errors, $name, 'item_failed', $itemErrors);
                    return ['ok' => false, 'value' => null];
                }

                return ['ok' => true, 'value' => $processed];
            }

            return ['ok' => true, 'value' => $value];
        }

        if ($useCast && $type === DataTypes::BOOLEAN->value) {
            $value = $this->normalizeBoolean($value);
        }

        if ($type === DataTypes::ARRAY->value) {
            if (!is_array($value)) {
                if ($useCast) {
                    $value = Arr::make($value)->val();
                } else {
                    $this->addError($errors, $name, 'expected_array');
                    return ['ok' => false, 'value' => null];
                }
            }
        }

        if ($type === DataTypes::OBJECT->value) {
            if (!is_object($value)) {
                $this->addError($errors, $name, 'expected_object');
                return ['ok' => false, 'value' => null];
            }
        }

        $valueObject = $this->makeValueObject($type, $value);
        if ($valueObject instanceof IVal) {
            if ($useCast) {
                $valueObject->cast();
                $value = $valueObject->val();
            } elseif (!$valueObject->isValid($value)) {
                $this->addError($errors, $name, 'invalid_type');
                return ['ok' => false, 'value' => null];
            }

            $constraintResult = $this->applyConstraints($valueObject, $value, $definition, $name, $input, $errors);
            if (!$constraintResult['ok']) {
                return ['ok' => false, 'value' => null];
            }
            $value = $constraintResult['value'];
        }

        return ['ok' => true, 'value' => $value];
    }

    protected function applyConstraints(IVal $valueObject, $value, FieldDefinition $definition, string $name, array $input, array &$errors): array
    {
        $constraints = $definition->constraints();
        if (empty($constraints)) {
            return ['ok' => true, 'value' => $value];
        }

        $constraintFailed = false;
        $constraintMessage = null;

        foreach ($constraints as $constraint) {
            if (!is_callable($constraint)) {
                continue;
            }
            $arity = $this->callableArity($constraint);
            $valueObject->constraint(function (&$current) use ($constraint, $arity, $name, $input, &$constraintFailed, &$constraintMessage) {
                $result = null;
                try {
                    if ($arity >= 3) {
                        $result = $constraint($current, $name, $input);
                    } elseif ($arity === 2) {
                        $result = $constraint($current, $name);
                    } else {
                        $result = $constraint($current);
                    }
                } catch (\Throwable $e) {
                    $constraintFailed = true;
                    $constraintMessage = $e->getMessage();
                    return;
                }

                if ($result === false) {
                    $constraintFailed = true;
                    $constraintMessage = 'constraint_failed';
                }
            });
        }

        try {
            $valueObject->val($value);
            $value = $valueObject->val();
        } catch (\Throwable $e) {
            $constraintFailed = true;
            $constraintMessage = $e->getMessage();
        }

        if ($constraintFailed) {
            $this->addError($errors, $name, $constraintMessage ?: 'constraint_failed');
            return ['ok' => false, 'value' => null];
        }

        return ['ok' => true, 'value' => $value];
    }

    protected function callableArity(callable $callable): int
    {
        try {
            if (is_array($callable) && count($callable) === 2) {
                $ref = new \ReflectionMethod($callable[0], $callable[1]);
            } elseif (is_string($callable)) {
                $ref = new \ReflectionFunction($callable);
            } elseif ($callable instanceof \Closure) {
                $ref = new \ReflectionFunction($callable);
            } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
                $ref = new \ReflectionMethod($callable, '__invoke');
            } else {
                return 1;
            }

            return $ref->getNumberOfParameters();
        } catch (\Throwable $e) {
            return 1;
        }
    }

    protected function normalizeDefinition(string $name, $definition): FieldDefinition
    {
        if ($definition instanceof FieldDefinition) {
            return $definition;
        }

        if ($definition instanceof self) {
            return new FieldDefinition($name, ['schema' => $definition]);
        }

        if (is_array($definition)) {
            $definition['schema'] = $definition['schema'] ?? ($definition['fields'] ?? null);
            return new FieldDefinition($name, $definition);
        }

        return new FieldDefinition($name, ['type' => $definition]);
    }

    protected function normalizeItemDefinition($definition): ?FieldDefinition
    {
        if ($definition instanceof FieldDefinition) {
            return $definition;
        }

        if ($definition instanceof self) {
            return new FieldDefinition('item', ['schema' => $definition]);
        }

        if (is_array($definition)) {
            return new FieldDefinition('item', $definition);
        }

        if ($definition !== null) {
            return new FieldDefinition('item', ['type' => $definition]);
        }

        return null;
    }

    protected function normalizeSchema($schema): ?self
    {
        if ($schema instanceof self) {
            return $schema;
        }

        if (is_array($schema)) {
            return new self($schema);
        }

        return null;
    }

    protected function normalizeType($type): ?string
    {
        if ($type instanceof DataTypes) {
            return $type->value;
        }

        if (!is_string($type)) {
            return null;
        }

        $type = strtolower($type);
        $aliases = [
            'int' => DataTypes::INTEGER->value,
            'integer' => DataTypes::INTEGER->value,
            'float' => DataTypes::FLOAT->value,
            'double' => DataTypes::DOUBLE->value,
            'number' => DataTypes::NUMBER->value,
            'bool' => DataTypes::BOOLEAN->value,
            'boolean' => DataTypes::BOOLEAN->value,
            'string' => DataTypes::STRING->value,
            'array' => DataTypes::ARRAY->value,
            'object' => DataTypes::OBJECT->value,
            'datetime' => DataTypes::DATETIME->value,
            'callable' => DataTypes::CALLABLE->value,
            'generic' => DataTypes::GENERIC->value,
        ];

        return $aliases[$type] ?? $type;
    }

    protected function makeValueObject(?string $type, $value): ?IVal
    {
        if (!$type) {
            return ValFactory::make(DataTypes::GENERIC, $value);
        }

        if ($type === DataTypes::OBJECT->value) {
            return ValFactory::make(DataTypes::GENERIC, $value);
        }

        return ValFactory::make($type, $value);
    }

    protected function normalizeData($data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if ($data instanceof Arr) {
            return $data->val();
        }

        if ($data instanceof Obj) {
            return $data->toArray();
        }

        if (is_object($data)) {
            return get_object_vars($data);
        }

        return [];
    }

    protected function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'n', 'off', ''], true)) {
                return false;
            }
        }

        return (bool)$value;
    }

    protected function resolveDefault(FieldDefinition $definition)
    {
        $default = $definition->defaultValue();
        if (is_callable($default)) {
            return $default($definition->name(), $this);
        }
        return $default;
    }

    protected function addError(array &$errors, string $field, string $message, $details = null): void
    {
        $entry = ['message' => $message];
        if ($details !== null) {
            $entry['details'] = $details;
        }

        if (!array_key_exists($field, $errors)) {
            $errors[$field] = [];
        }

        $errors[$field][] = $entry;
    }

    protected function arrayValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Arr) {
            return $value->val();
        }

        return [];
    }

    protected function setValue(string $field, $value): void
    {
        $current = $this->_data[$field] ?? null;
        if ($current instanceof IVal) {
            $current->val($value);
            return;
        }
        $this->_data[$field] = $value;
    }
}
