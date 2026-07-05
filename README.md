# DevElation: A PHP Library for Graceful Development

Welcome to the central documentation of **DevElation**, a comprehensive PHP library designed to simplify the development of large and complex projects. This library addresses the intricacies of modern PHP development with a suite of tools that cater to a wide range of functionalities, from basic data type handling to advanced system management and beyond. It is a property of Blue Fission Technology and is currently available under the MIT license.

## Philosophy

DevElation is built with the philosophy of reducing code complexity and promoting interconnectedness. It embraces the notion that robust application development can be made more approachable through a suite of well-organized, intuitive modules. The library is not just a bag of utility functions; it is a set of hookable value objects, data objects, behaviors, services, and helpers that are meant to participate in an application lifecycle.

Central to DevElation's design is the principle of rapid prototyping without throwing away architecture. Primitives such as `Val`, `Str`, `Arr`, `Num`, `Flag`, `Date`, `Func`, `Ref`, and `Obj` should be treated as first-class citizens when values are captured, mutated, validated, observed, or passed through more than a trivial boundary. Static helpers remain appropriate for one-off hookable operations, while fluent objects are preferred when a value has a lifecycle.

The library's dependency injection-friendly architecture means that scaling and enhancing functionality is often a matter of changing the injected class or storage backend. File, session, memory, SQL, Mongo, queue, schema, graph, service, connection, CLI, HTTP, HTML, and parsing classes are intentionally shaped around repeatable signatures so consumers can grow from simple scripts to structured systems without rewriting every call site.

The pervasive event-driven approach throughout DevElation, where even simple data types can participate in events, hooks, filters, constraints, snapshots, and dynamic slots, reflects the library's commitment to intricate yet manageable systems. Complexity should live with the object that owns the data or behavior, not in scattered procedural glue.

This granular empowerment leads to a development style where maintainability and readability are not at odds with power. DevElation code should prefer package-owned classes and interfaces when they already exist, keep features general and reusable, avoid consumer-specific coupling, and expose stable APIs that can be inherited, decorated, filtered, or composed.

In practice:

- Use DevElation objects for values and structures that will be worked with repeatedly.
- Use static helpers for single hookable operations.
- Use global helper functions as constructor/factory shortcuts only.
- Let returned objects own their fluent behavior.
- Prefer DevElation data, connection, service, CLI, HTML, parsing, security, and system utilities over ad-hoc raw PHP when the package already owns the capability.
- Keep public features package-owned and broadly useful; consuming projects should consume or extend DevElation, not define its internal concerns.

## Features Overview

### Event Handling (`Behavior`)
DevElation implements a behavior-driven event handling system, which includes `Event`, `State`, and `Action`. These behaviors allow for reactive and decoupled components, making your application more modular and maintainable.

- [Event Handling Documentation](behavior.md)
- [Behavior Lifecycle Patterns](parser-runtime-behaviors.md)
- [Hooks, Filters, And Events Reference](hooks_events.md)

### Data Types
A collection of wrapper classes around PHP's primitive data types that offer enhanced functionality and utility methods.

- [Data Types Documentation](datatypes.md)
- [Ref Primitive Documentation](ref.md)
- [DevElation Capability Surface](capability_surface.md)
- [Usage Readiness Checklist](usage_readiness_checklist.md)

Static helpers: most `Val`/`Obj`-based classes expose their underscored instance helpers as static shorthand. For example, `Str` has an internal `_pluralize()` instance method which can be invoked statically via `Str::pluralize('comment')` thanks to `Val::__callStatic`. This pattern is used throughout the library and examples.

Global helper functions: Composer autoloads `src/functions.php`, which provides short factory entrypoints for the highest-use object lifecycles. These helpers instantiate or factory-wrap objects; they do not replace object methods. Chain from the returned object for behavior.

```php
use BlueFission\DataTypes;

$title = str(' release notes ')->trim()->capitalize()->val();
$items = arr(['first', 'second'])->reverse()->join(', ')->val();
$record = obj(['count' => 3], ['count' => DataTypes::NUMBER]);
$document = doc()->contents('Draft text');
$handle = ref(fopen('php://temp', 'r+'))->owned(true);
$handle->write('data');
```

Available helpers:

- `val(mixed $value = null): Val`
- `str(mixed $value = null): Str`
- `arr(mixed $value = null): Arr`
- `num(mixed $value = null): Num`
- `flag(mixed $value = null): Flag`
- `func(mixed $value = null): Func`
- `ref(mixed $value = null): Ref`
- `obj(array|object|null $data = null, array $types = []): Obj`
- `collect(mixed $value = null): Collection`
- `datetime(mixed $value = null): Date`
- `filesystem(mixed $config = null): FileSystem`
- `doc(): Data\File`
- `directory(?IData $storage = null): Data\Directory`

The names intentionally avoid PHP built-ins such as `file()` and `date()`. Use
`doc()` for a DevElation file object and `datetime()` for a DevElation date
object.

Helper API notes:

- Prefer an object lifecycle when a value is changed or inspected more than a couple of times. Prefer static helpers for one-off hookable operations.
- Use `Val::make($value)`, `Val::copy()`, `Val::as($target)`, `snapshot()`, `recall()`, `reset()`, and `if(...)->then(...)->otherwise(...)` when generic values need lifecycle, branching, or change tracking.
- Use `Arr::has($array, $value)` for value checks. Use `Arr::hasKey($array, $key)` for key checks, and `Arr::hasValue($array, $value, true)` or `Arr::contains($array, $value, true)` when strict value matching matters.
- Use `Arr::merge($base, $next)` when associative keys should be replaced recursively and numeric list values should be appended when unique. Use `Arr::append($base, $next)` for append-only numeric list behavior.
- Use `Arr::filter($array, $callback)`, `Arr::map($array, $callback)`, `Arr::diff($left, $right)`, `Arr::intersect($left, $right)`, `Arr::slice($array, $offset, $length)`, `Arr::splice($array, $replacement, $offset, $length)`, `Arr::join($array, $separator)`, and `Arr::reverse($array, $preserveKeys)` for common array transforms that should stay on the DevElation helper surface.
- `Arr::map()` / `Arr::filter()` and `Collection::map()` / `Collection::filter()` share traversal semantics: callbacks may accept `value` or `value, key`, and retained or mapped values preserve their original keys.
- Use `Arr::values($array)` to reindex list values while preserving order. Use `BlueFission\Net\HTTP::pathSegment($segment)` to encode a single URL path segment; `HTTP::urlScheme()`, `HTTP::urlHost()`, `HTTP::urlPort()`, `HTTP::urlPath()`, or `HTTP::urlParts()` for normalized URL component parsing; `HTTP::jsonDecode()` for deterministic JSON fallback handling; and `HTTP::headerLine()`, `HTTP::statusText()`, or `HTTP::statusLine()` for side-effect-free HTTP metadata helpers.
- Use `Arr::mergeRecursive($base, $next)` when associative keys should be replaced recursively and numeric lists should append in order while preserving duplicates.
- Use `Str::repeat($value, $times)` or the explicit `Str::strRepeat($value, $times)` alias as the canonical static helper for `str_repeat`-style behavior. Repeat counts must be non-negative; existing instance usage such as `Str::make(' ')->repeat(4)->val()` remains supported.
- Use `Str::startsWith($value, $needle)`, `Str::endsWith($value, $needle)`, `Str::match($left, $right, Str::IGNORE_CASE)`, `Str::snake($value)`, `Str::pluralize($value)`, and `Str::size($value)` for string boundary, equality, casing, inflection, and length checks. Values are string-cast before comparison and whitespace remains significant.
- Use `Date::formatTimestamp($timestamp, 'Y-m-d')` as a concise replacement for `date($format, $timestamp)`.
- Use `Num::plus()`, `Num::minus()`, `Num::times()`, and `Num::by()` as readable aliases for fluent math. Math helpers unwrap `Val` objects when appropriate.
- Use `Num::isIntStrict()`, `Num::isFloatStrict()` / `Num::isDoubleStrict()`, and `Flag::isBoolStrict()` / `Flag::isBooleanStrict()` when native scalar type checks must not coerce strings or numeric values.
- Use `Num::deg2rad($degrees)`, `Num::rad2deg($radians)`, `Num::sin($radians)`, `Num::cos($radians)`, and `Num::atan2($y, $x)` for hookable angle and trigonometry helpers. Angles passed to trigonometry helpers are radians.
- Use `Ref::resource($handle, ['owned' => false])`, `Ref::open($target, ['mode' => 'r'])`, `Ref::bind($value)`, and `ref($value)` when PHP references, stream resources, process pipes, or runtime handles need explicit ownership, read/write helpers, hooks, or lifecycle events. Keep native handles where direct PHP interop is clearer.
- `BlueFission\Data\FileSystem::fileExists($path)` checks concrete file paths without initializing storage state. `BlueFission\Data\File::exists()` / `isReachable()` and `BlueFission\Data\Directory::exists()` / `isReachable()` can check explicit paths or hierarchy labels without creating missing paths.
- Use `BlueFission\Data\FileSystem::lines($eol)` for read-only file line values and `FileSystem::entries()` for sorted directory entry values. Missing targets return empty arrays and are not created.
- Use `BlueFission\Connections\Stdio::input()` or `Stdio::readInput()` to read request/body streams without interactive `stream_select()` polling. Empty or unreadable input returns an empty string.

### DateTime Handling
Sophisticated date and time manipulation with object-oriented principles, extending PHP's native `DateTime` class.

- [DateTime Documentation](date.md)

### Complex Object Prototyping
An advanced object class, `Obj`, extends the capabilities of `Val` for managing complex data structures.

- [Object Prototyping Documentation](object.md)

### Collections
Manage groups of items with powerful collection classes, providing utilities for array-like operations on objects.

- [Collections Documentation](collections.md)

### Connections
Facilitate connectivity to various data sources and services, such as MySQL, cURL, and streams.

- [Connections Documentation](connections.md)

### Data Management
Handle queues, storage solutions, databases, Redis, MemQ, files, and logs with a unified approach to data manipulation and persistence.

- [Data Management Documentation](data_management.md)
- [SQLite Storage Contract](sqlite.md)
- [Schema Documentation](schema.md)
- [Graph Documentation](graph.md)
- [Prototypes Documentation](prototypes.md)

### HTML Building Tools
Construct HTML elements for forms, tables, and templating with ease, improving the speed of frontend development.

- [HTML Tools Documentation](html_tools.md)

### Parsing & Templating
Parsing, tag execution, includes, template sections, and scoped loop helpers for declarative templating workflows.

- [Parsing & Templating Documentation](parsing.md)

### Network Services
Tools for handling email, HTTP requests, and IP operations, essential for developing web services and networked applications.

- [Network Services Documentation](network_services.md)

### Application Framework
Beginnings of an application framework, offering service and routing management to pave the way for complex application architectures.

- [Application Framework Documentation](app_framework.md)

### System Tools
A suite of tools for interacting with the operating system, including CLI utilities, process control, machine details, statistics, and asynchronous operations.

- [System Tools Documentation](system_tools.md)

### Utilities
A set of utility functions and tools for administrative alerts, logging, IP blocking, and safeguards against runaway scripts.

- [Utilities Documentation](utilities.md)

### Security Tools
Hashing helpers for checksums, content IDs, and signatures.

- [Security Documentation](security.md)

### Testing
Guidance for running PHPUnit tests and enabling optional integration coverage.

- [Testing Documentation](tests.md)
- [Coding Standards](coding_standards.md)

### Examples
Sample applications that demonstrate DevElation’s flexibility:

- Session-backed todo list using `Arr`, `Date`, `Session` storage, HTML helpers, and templates: `examples/todo/index.php`
- Simple comment thread with voting using `Arr`, `Str`, `Session` storage, HTML helpers, and templates: `examples/comments/index.php`
- CLI territory game using the behavioral engine (`Behaves`) and an `Arr`-backed log: `examples/game/gangs.php`
- CLI status report using args, tables, and progress bars: `examples/cli/report.php`
- Helper workflow using global factory helpers plus primitive, file, HTTP, date, flag, and security objects: `examples/helpers/workflow.php`
- HTTP/API packet builder using request normalization, headers, JSON, and status helpers: `examples/http/api_packet.php`
- Additional walkthroughs live in `examples/README.md`

## Quick Start Examples

### Fluent values and global helper entrypoints

```php
use BlueFission\DataTypes;

$names = arr(['Ada Lovelace', 'Grace Hopper'])
    ->map(fn (string $name): string => str($name)->trim()->val())
    ->reverse()
    ->join(', ')
    ->val();

$record = obj(['count' => 3], ['count' => DataTypes::NUMBER]);
$record->exposeValueObject();
$total = $record->field('count')->plus(num(2))->val();

$document = doc()->contents('Processed: ' . $names);
$timestamp = datetime('2026-07-04')->timestamp();
```

Global helpers are entrypoints into DevElation objects. They should create the
object; the object should own the behavior.

### CLI utilities: args, tables, and progress

```php
use BlueFission\Cli\Args;
use BlueFission\Cli\Args\OptionDefinition;
use BlueFission\Cli\Console;

$args = (new Args())
    ->addOption(new OptionDefinition('limit', [
        'short' => 'l',
        'type' => 'int',
        'default' => 5,
        'description' => 'Number of rows to show.',
    ]))
    ->parse($argv);

$options = $args->options();
$limit = $options['limit'] ?? 5;

$console = new Console();
$console->writeln($console->color('Report', 'cyan', ['bold']));
$console->writeln($console->table(['Item', 'Count'], [
    ['alpha', $limit],
]));

for ($i = 1; $i <= $limit; $i++) {
    $console->rewriteLine($console->progress($limit, $i));
}
$console->writeln('');

$spinner = $console->spinner('Working');
for ($i = 0; $i < 12; $i++) {
    $console->rewriteLine($spinner->tick());
    usleep(120000);
}
$spinner->stop();
$console->writeln('');

$result = $console->working('Fetching', function () {
    usleep(250000);
    return 'ok';
});
$console->writeln('Result: ' . $result);
```

### Storage pipeline: session-backed data

```php
use BlueFission\Data\Storage\Session;

$store = new Session(['name' => 'todos']);
$store->activate()->read();

$todos = (array)($store->contents() ?? []);
$todos[] = ['task' => 'Ship docs', 'due' => '2026-01-10'];

$store->assign($todos);
$store->write();
```

### Template loops: scoped current items and nested partials

```php
use BlueFission\Parsing\Parser;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Registry\ExecutorRegistry;
use BlueFission\Parsing\Registry\PreparerRegistry;

TagRegistry::registerDefaults();
RendererRegistry::registerDefaults();
ExecutorRegistry::registerDefaults();
PreparerRegistry::registerDefaults();

$parser = new Parser('{#each items=chapters glue="|"}{$current.title}:@include(\'sections.tpl\'){/each}');
$parser->setVariables([
    'chapters' => [
        [
            'title' => 'Chapter 1',
            'sections' => [
                ['title' => 'Section 1'],
                ['title' => 'Section 2'],
            ],
        ],
    ],
]);
$parser->setIncludePaths(['modules' => __DIR__ . '/templates']);

echo $parser->render();
```

Inside `#each`, the current item is available as a scoped `current` value, so `{$current.title}`, `items=current.sections`, `{@current}`, and the shorthand `{.title}` all resolve against the active loop item. Nested `#each` blocks in the same file are supported, and partial/include-based nesting remains the cleaner path when you want reusable template boundaries. Quoted attribute strings can also interpolate scoped values with `[[...]]`, for example `thread="book:[[book.slug|slug]]:chapter:[[chapter|pad:2]]"`.

## Usage

Install DevElation with Composer:

```bash
composer require bluefission/develation
```

Composer autoload registers DevElation classes and the global helper function
file. After requiring `vendor/autoload.php`, helpers such as `arr()`, `str()`,
`obj()`, `collect()`, `datetime()`, `filesystem()`, `doc()`, and `directory()`
are available.

The core package does not require the optional Ratchet websocket transport. If
you use `BlueFission\Async\Sock`, install Ratchet in applications that can
satisfy its dependency constraints:

```bash
composer require cboden/ratchet
```

The canonical source repository is `BlueFissionTech/develation` on GitHub.

```bash
git clone https://github.com/BlueFissionTech/develation.git
```

## Contributions

DevElation welcomes contributions from the open-source community. Whether you're a seasoned developer or just starting, your input is valued. If you have ideas on how to expand the library's capabilities, especially in areas of automation, smart technologies, and AI, please consider contributing.
