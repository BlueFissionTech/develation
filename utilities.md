# Utilities

Utilities provide small, reusable helpers that do not fit neatly into the other modules. They are intended for glue code, safety checks, and convenience features.

## Key Classes

- `Utils\Util`: alerts, storage helpers, and guardrails.
- `Utils\Mem`: memory pool management with optional persistence.
- `Utils\Loader`: class loader for local discovery.

## Quick Start: Guard a Loop

```php
use BlueFission\Utils\Util;

$count = 0;
while (true) {
    Util::parachute($count, 1000);
    // work
}
```

## Quick Start: CLI Storage

```php
use BlueFission\Utils\Util;

Util::store('last_job', ['id' => 7, 'status' => 'done']);
$result = Util::store('last_job');
```

## Quick Start: Memory Pool

```php
use BlueFission\Utils\Mem;

$object = new stdClass();
Mem::register($object);
Mem::assess();
Mem::flush();
```

## Related

Storage paths for CLI sessions live under `src/Utils/storage`.
