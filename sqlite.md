# SQLite Storage Contract

`BlueFission\Data\Storage\SQLite` is the DevElation storage adapter for local SQLite databases. It follows the same broad storage shape as the other `Data\Storage` classes while exposing SQLite-specific read, query, and schema behavior.

This contract is intentionally storage-level. Application models should own business validation, repository methods, migrations, and domain-specific query names.

## Configuration

Common configuration keys:

- `location`: SQLite database file path. `null` uses the connection default.
- `name`: table name or table list used by the storage object.
- `fields`: optional active field list for reads and writes.
- `key`: optional key field for write/create behavior.
- `ignore_null`: skip null values when writing.
- `temporary`: create a temporary table during auto-create.
- `set_defaults`: include scalar defaults during auto-create inference.

The class requires PHP's `SQLite3` extension. DevElation tests skip SQLite coverage automatically when that extension is unavailable.

## Result Materialization

`read()` preserves the historical single-row behavior:

- the default row window is `0, 1`
- the first row is assigned into the storage object's data payload
- the active SQLite result handle is retained internally
- `fetchRows()` can still materialize rows from the active result handle

Use `readAll()` when the caller wants all matching rows:

```php
use BlueFission\Data\Storage\SQLite;

$store = new SQLite([
    'location' => __DIR__ . '/app.sqlite',
    'name' => 'items',
]);

$store->activate()->readAll();
$rows = $store->fetchRows();
```

Use `all()` for the concise list path:

```php
$rows = $store->activate()->all();
```

Materialization helpers:

- `field($name)` reads the first materialized row value after `read()` or `run()`.
- `data()` returns the assigned first-row payload.
- `contents()` returns the active result object when present, otherwise the assigned data payload.
- `fetchRows()` returns an array of associative rows from the active result and resets the cursor.
- `readAll()` removes the default limit for the next read.
- `all()` calls `readAll()` and returns `fetchRows()`.

## Query, Status, And Error Diagnostics

SQLite storage exposes the last operation state through:

- `query()`: last SQL statement sent through the adapter.
- `status()`: DevElation operation status such as `SQLite::STATUS_SUCCESS` or `SQLite::STATUS_FAILED`.
- `error()`: underlying connection status when a connection exists; otherwise the object status.
- `lastRow()`: last affected row id captured during writes when available.

Recommended diagnostic shape:

```php
$store->activate()->readAll();

$diagnostics = [
    'query' => $store->query(),
    'status' => $store->status(),
    'error' => $store->error(),
    'row_count' => count($store->fetchRows()),
];
```

Do not parse SQL strings for application decisions. Use `query()` for diagnostics, trace output, and test assertions only.

## Schema Creation

`SQLite` can auto-create a table from the current data payload when a write path needs a missing table. Auto-create infers SQLite column types from values:

- numeric integers become `INTEGER`
- numeric floats become `REAL`
- date-like strings become `DATETIME`
- ordinary strings become `TEXT`
- arrays, objects, and associative payloads become `TEXT`
- fields ending in `id` can become integer key fields

This auto-create path is intended as a convenience for simple local storage and prototypes. It is not a migration system.

When explicit schema shape matters, use the storage structure helpers instead of relying on auto-create inference:

- `BlueFission\Data\Storage\Structure\SQLiteStructure`
- `BlueFission\Data\Storage\Structure\SQLiteField`
- `BlueFission\Data\Storage\Structure\SQLiteScaffold`

Application-level migrations, data backfills, indexes, and destructive schema changes remain outside the responsibility of `SQLite`.

## Tests

Relevant coverage:

- `tests/Data/Storage/SQLiteTest.php`: default single-row reads, all-row materialization, query/status diagnostics.
- `tests/Data/Storage/SQLiteAutoCreateTest.php`: auto-create type inference.
- `tests/Data/Storage/Structure/SQLiteFieldTest.php`: explicit SQLite field definitions.
- `tests/Connections/Database/SQLiteLinkTest.php`: connection-level table detection.

Run the focused suite with:

```bash
vendor/bin/phpunit --do-not-cache-result tests/Data/Storage/SQLiteTest.php
vendor/bin/phpunit --do-not-cache-result tests/Data/Storage/SQLiteAutoCreateTest.php
vendor/bin/phpunit --do-not-cache-result tests/Data/Storage/Structure/SQLiteFieldTest.php
vendor/bin/phpunit --do-not-cache-result tests/Connections/Database/SQLiteLinkTest.php
```
