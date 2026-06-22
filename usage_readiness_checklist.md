# DevElation Usage Readiness Checklist

Use this checklist when introducing DevElation into a PHP package or application, or when reviewing whether existing code is depending on undocumented behavior.

The goal is not to wrap every PHP primitive. The goal is to use DevElation's documented surfaces when they provide value: hooks, consistent helper names, behavioral events, storage substitution, and object/value normalization.

## Primitive Idioms

Arrays:

- Use `Arr::hasKey($array, $key)` for key existence.
- Use `Arr::has($array, $value)` for loose value checks only when loose matching is intended.
- Use `Arr::hasValue($array, $value, true)` or `Arr::contains($array, $value, true)` for strict value checks.
- Use `Arr::append($base, $next)` for append-only list behavior.
- Use `Arr::merge($base, $next)` when associative keys should replace recursively and numeric list values should append when unique.
- Use `Arr::mergeRecursive($base, $next)` when numeric lists should append in order while preserving duplicates.
- Use `Arr::filter($array, $callback)`, `Arr::map($array, $callback)`, `Arr::slice(...)`, `Arr::diff(...)`, `Arr::intersect(...)`, `Arr::values(...)`, `Arr::reverse(...)`, and `Arr::count($array)` for common transforms that should stay on the helper surface.

Strings:

- Use `Str::trim()`, `Str::lower()`, `Str::upper()`, and related helpers when normalization should remain hookable.
- Use `Str::match($left, $right, Str::IGNORE_CASE)` for case-insensitive equality.
- Use `Str::startsWith($value, $needle)` and `Str::endsWith($value, $needle)` for string boundary checks.
- Use `Str::repeat($value, $times)` or `Str::strRepeat($value, $times)` as the documented repeat helpers.
- Use `Str::make($value)->helper(...)->val()` when chained object-style normalization is clearer than static calls.

Numbers, flags, and dates:

- Use `Num` for numeric conversion, arithmetic, formatting, and comparison helpers that should be hookable.
- Use `Num::deg2rad()`, `Num::rad2deg()`, `Num::sin()`, `Num::cos()`, and `Num::atan2()` for angle and trigonometry operations that should remain on the helper surface.
- Use `Flag` for boolean-style values instead of scattering one-off string comparisons.
- Use `Date` for date/time value handling and `Date::formatTimestamp($timestamp, $format)` for timestamp formatting.

Objects and values:

- Use `Obj::assign($arrayOrObject)` to populate structured values from associative data.
- Use `Obj::field($name, $value)` for explicit field writes and `Obj::field($name)` for reads.
- Use `Val`/`Obj` wrappers when hooks, validation, events, or chained helper semantics matter.
- Return native values with `val()`, `data()`, `field()`, or documented terminal helpers before crossing package boundaries.

## Safe Population Examples

Object payload:

```php
use BlueFission\Obj;

$profile = new Obj();
$profile->assign([
    'name' => 'Ada',
    'role' => 'maintainer',
]);

$profile->field('role', 'reviewer');
$payload = $profile->data();
```

String normalization:

```php
use BlueFission\Str;

$slug = Str::make(' Launch Notes ')
    ->trim()
    ->lower()
    ->val();
```

Array key/value checks:

```php
use BlueFission\Arr;

$settings = ['mode' => 'strict', 'enabled' => true];

if (Arr::hasKey($settings, 'mode')) {
    $mode = $settings['mode'];
}

$hasEnabledValue = Arr::hasValue($settings, true, true);
```

## Data Access And Query Usage

Use `BlueFission\Data` classes when a caller benefits from a substitutable storage interface, behavior events, and status diagnostics.

General storage pattern:

```php
use BlueFission\Data\Storage\Session;

$store = new Session(['name' => 'todos']);
$store->activate()->read();

$todos = (array)($store->contents() ?? []);
$todos[] = ['task' => 'Review release checklist'];

$store->assign($todos);
$store->write();
```

Database storage pattern:

```php
use BlueFission\Data\Storage\SQLite;

$items = new SQLite([
    'location' => __DIR__ . '/app.sqlite',
    'name' => 'items',
]);

$rows = $items->activate()->all();
$status = $items->status();
$query = $items->query();
```

Repository-style code should keep domain names at the application layer and use DevElation storage objects for the read/write/query/status mechanics.

## Service And Utility Usage

- Use `BlueFission\Connections` classes for connection lifecycle, status, and query helpers.
- Use `BlueFission\Connections\Stdio::input()` or `Stdio::readInput()` for non-interactive body input.
- Use `BlueFission\Net\HTTP` helpers for URL component parsing, path segment encoding, header lines, and status lines.
- Use `BlueFission\Cli` utilities for command arguments, console tables, progress bars, spinners, and status output.
- Use behavioral events (`Event::SUCCESS`, `Event::FAILURE`, `Event::PROCESSED`, and related states/actions) when instrumentation should remain decoupled from direct method calls.

## Assumptions To Avoid

- Do not assume `Arr::has()` means key existence; use `Arr::hasKey()` for keys.
- Do not assume helper calls are pure if the documented helper mutates wrapper state.
- Do not assume constructors create files, directories, database tables, or external resources unless the class documents that side effect.
- Do not assume optional services are available in a clean install or default test run.
- Do not parse diagnostic SQL strings for application decisions.
- Do not depend on private underscored helpers directly; use the documented public or static helper surface.
- Do not store secrets, credentials, or full runtime state in behavior metadata or traces.

## Review Checklist

- Primitive checks use DevElation helpers when hooks or consistency matter.
- Key checks and value checks are intentionally distinct.
- String and array normalization paths are covered by helper calls or tests.
- Object population uses `assign()` or `field()` rather than ad hoc property mutation.
- Storage code checks `status()` and uses documented result materialization.
- Query diagnostics are logged or traced only as diagnostics.
- Optional integrations remain opt-in.
- Public code depends only on documented methods, constants, and return shapes.
- Application-specific repository names, lifecycle names, and migrations stay outside DevElation core.
- New helper requests make sense for DevElation as a general-purpose library, not only one caller.
