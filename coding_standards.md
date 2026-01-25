# Coding Standards and Patterns

This document summarizes common DevElation coding patterns and conventions used across `src/`, with examples for contributors and library users.

## Project Layout

- Source lives in `src/`.
- Tests live in `tests/` and mirror the `src/` namespaces.
- Examples live in `examples/`.

## Naming and Structure

Derived from internal notes in `src/Arr.php` and established conventions:

- Class properties use `snake_case`; public methods use `camelCase`.
- Add type hints for arguments and returns whenever possible.
- Avoid repeated inline negation in a method. Prefer early returns.
- Trigger `Event::CHANGE` when mutating `_data`.
- Provide PHPDoc for public methods with `@param`, `@return`, and `@throws`.
- Avoid commented-out legacy code (use version control history instead).

## Value Objects (Val)

Prefer `Val` derivatives for data manipulation:

- `Str`, `Num`, `Arr`, `Flag`, `Date` provide safe operations and event hooks.
- Use `Val::isNull`, `Val::isNotNull`, `Val::isEmpty` instead of raw PHP checks.
- Use `Val::constraint` for validation and normalization logic.

Static helper mapping:

```php
use BlueFission\Str;

$plural = Str::pluralize('comment');
```

The static call maps to the underscored instance method (`_pluralize`) via `Val::__callStatic`.

Remember the `grab()` and `use()` pattern:

```php
use BlueFission\Str;

Str::is('hello');
$last = Str::grab();
$instance = Str::use();
```

## Objects (Obj)

Use `Obj` with typed fields instead of getters/setters:

- Use `$obj->field('name', $value)` or `$obj->name = $value`.
- Typed fields are defined in `_types` using `DataTypes`.
- Use `_exposeValueObject` if you need the value object instance.
- Use `_lockDataType` to enforce strict typing in assignments.

```php
class Person extends \BlueFission\Obj {
    protected $_data = ['name' => null, 'age' => null];
    protected $_types = [
        'name' => \BlueFission\DataTypes::STRING,
        'age' => \BlueFission\DataTypes::INTEGER,
    ];
}
```

## Behaviors and Events

Most core classes are behavior-aware and use `Behaves`:

- Use `perform()` for actions and states.
- Use `dispatch()` for events.
- Use `when()` to register handlers.

```php
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;

$obj->when(Event::PROCESSED, function ($behavior, Meta $meta) {
    // handle output
});
```

## Hooks and Filters (DevElation)

Use DevElation hooks in input/output paths:

- `DevElation::apply('_in', $value)` before processing.
- `DevElation::apply('_out', $value)` before returning.
- `DevElation::do('_before', [...])` and `DevElation::do('_after', [...])` around work.

Example pattern:

```php
use BlueFission\DevElation as Dev;

$input = Dev::apply('_in', $input);
Dev::do('_before', [$input, $this]);
// work
$output = Dev::apply('_out', $output);
Dev::do('_after', [$output, $this]);
```

Note: hooks are no-ops unless `DevElation::up()` is called.

## Dependency Injection and Config

- Accept dependencies via constructor arguments or config arrays.
- Use `Behavioral\Configurable` to centralize configuration.
- Validate config with `Arr::isAssoc` and `Val::isNotNull` checks.

## Validation and Constraints

Prefer `Val::constraint` for enforcing validation rules and data normalization.

```php
$value = \BlueFission\Str::make('  name  ')
    ->constraint(function (&$v) { $v = trim($v); });
```

## Utilities

Leverage existing helpers:

- `Utils\Util` for parachutes, storage, and global lookups.
- `Utils\Mem` for memory pooling.
- `Utils\Loader` for class discovery when needed.

## Testing

- Use PHPUnit tests in `tests/` with namespaces matching `src/`.
- Use `tests/Support/TestEnvironment` for temp directories and opt-in integrations.
- Keep integration tests gated by env vars.

## Suggested Patterns for New Code

- Prefer `DataTypes` + `ValFactory` for typed value creation.
- Use `Schema` for structured input validation.
- Use `Security\Hash` for hashing and content IDs instead of raw `hash_*` calls.
- Emit `Event::PROCESSED` on successful transforms.
