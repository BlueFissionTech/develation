# Data Management

DevElation data tools provide a consistent read/write interface across files, sessions, databases, queues, and logs. Most classes in `BlueFission\Data` are behavioral, so you can hook into `Event::SUCCESS`, `Event::FAILURE`, or `Event::PROCESSED` when you need instrumentation.

## Core Building Blocks

- `Data`: a behavior-aware base that standardizes read, write, save, and delete actions.
- `Storage`: a concrete data layer with `activate()`, `read()`, `write()`, `delete()` and `contents()`.
- `Datasource`: a lightweight adapter for external data sources.
- `Queues`: in-memory and persistent queue options (FIFO and FILO).
- `Log`: push structured messages to file, email, or system logs.

## Quick Start: Disk Storage

```php
use BlueFission\Data\Storage\Disk;

$store = new Disk([
    'location' => __DIR__ . '/data',
    'name' => 'cache.json',
]);

$store->activate()->read();
$payload = (array)($store->contents() ?? []);
$payload['last_run'] = time();

$store->assign($payload);
$store->write();
```

## Schema Validation

`BlueFission\Data\Schema` lets you define typed fields, cast input, and validate structured data.

```php
use BlueFission\Data\Schema;

$schema = new Schema([
    'name' => ['type' => 'string', 'required' => true],
    'age' => ['type' => 'int', 'default' => 0],
    'active' => ['type' => 'bool', 'default' => true],
    'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
], ['strict' => true]);

$result = $schema->transform([
    'name' => 123,
    'age' => '42',
    'tags' => ['alpha', 9],
]);

if ($schema->errors()) {
    // handle validation errors
}
```

See `schema.md` for nested schemas, arrays, and constraint patterns.

## Graph Utilities

`BlueFission\Data\Graph` provides lightweight node/edge helpers and a
shortest-path utility when you need an in-memory graph structure.

```php
use BlueFission\Data\Graph\Graph;

$graph = new Graph([], false);
$graph->connect('a', 'b', ['weight' => 2]);
$graph->connect('b', 'c', ['weight' => 1]);

$path = $graph->shortestPath('a', 'c', function (array $edge): int {
    return (int)($edge['weight'] ?? 1);
});
```

See `graph.md` for more examples and event hooks.

## Quick Start: Session Storage

```php
use BlueFission\Data\Storage\Session;

$session = new Session(['name' => 'todos']);
$session->activate()->read();

$todos = (array)($session->contents() ?? []);
$todos[] = ['task' => 'Ship docs'];

$session->assign($todos);
$session->write();
```

## Queue Example

```php
use BlueFission\Data\Queues\Queue;

Queue::setMode(Queue::FIFO);
Queue::enqueue('jobs', ['id' => 1, 'task' => 'sync']);
$job = Queue::dequeue('jobs');
```

## Logging Example

```php
use BlueFission\Data\Log;

$log = new Log(['file' => 'application.log']);
$log->push('queue processed')->write();
```

## Related

- Storage implementations live under `src/Data/Storage` (MySQL, SQLite, Mongo, Memcached, Disk, Session).
- Queue implementations live under `src/Data/Queues`.
- Optional integration test setup is documented in `tests.md`.
