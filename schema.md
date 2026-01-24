# Schema Validation

`BlueFission\Data\Schema` provides a typed schema layer for validation, casting, and normalization. It is built on top of `Obj`, `DataTypes`, and `Val` constraints, so it fits the same DevElation behavioral and hookable patterns as the rest of the library.

## Quick Start

```php
use BlueFission\Data\Schema;

$schema = new Schema([
    'name' => ['type' => 'string', 'required' => true],
    'age' => ['type' => 'int', 'default' => 0],
    'active' => ['type' => 'bool', 'default' => true],
], ['strict' => true]);

$result = $schema->transform([
    'name' => 123,
    'age' => '42',
]);

if ($schema->errors()) {
    // handle errors
}
```

## Nested Schemas

```php
use BlueFission\Data\Schema;

$address = new Schema([
    'line1' => ['type' => 'string', 'required' => true],
    'city' => ['type' => 'string', 'required' => true],
]);

$user = new Schema([
    'id' => ['type' => 'int', 'required' => true],
    'address' => ['schema' => $address],
]);

$result = $user->transform([
    'id' => '7',
    'address' => ['line1' => '123 Main', 'city' => 'Austin'],
]);
```

## Array Items

```php
$schema = new Schema([
    'tags' => [
        'type' => 'array',
        'items' => ['type' => 'string'],
    ],
]);
```

## Constraints

Constraints use `Val::constraint()` under the hood and can mutate or reject values.

```php
use BlueFission\Data\Schema;
use BlueFission\Data\Schema\FieldDefinition;

$schema = new Schema([
    'slug' => new FieldDefinition('slug', [
        'type' => 'string',
        'constraints' => function (&$value) {
            $value = trim((string)$value);
            return $value !== '';
        },
    ]),
]);
```

## Strict Mode

If `strict` is enabled, unknown fields produce errors. Otherwise, they are passed through.

```php
$schema = new Schema(['name' => ['type' => 'string']], ['strict' => true]);
$schema->apply(['name' => 'Ava', 'extra' => true]);
```

## Errors

Errors are grouped by field name and include message and optional details.

```php
$errors = $schema->errors();
// [
//   'name' => [ ['message' => 'required'] ],
//   'extra' => [ ['message' => 'unknown_field'] ],
// ]
```

## Behavior Hooks

`Schema` triggers standard behaviors:

- `Event::SUCCESS` on valid transforms
- `Event::FAILURE` on validation failures
- `Event::PROCESSED` for every apply/transform call

You can also use `DevElation::apply()` and `DevElation::do()` to hook inputs/outputs.
