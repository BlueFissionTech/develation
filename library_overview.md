# DevElation Library Overview

This document is a practical, plain-language guide to the philosophy, patterns,
and strengths of the DevElation PHP library. It is written for junior developers
and for experienced engineers who are new to the Blue Fission style.

## Philosophy (Why This Library Exists)

DevElation is built around a few core ideas:

1) **Typed value objects over raw primitives**  
   Strings, numbers, arrays, and booleans are treated as first-class objects.
   These "Val" objects allow validation, constraints, and consistent chaining.

2) **Dynamic data objects with consistent access**  
   `Obj` lets you define typed fields and use uniform `assign()` and field
   access without writing repetitive getters/setters.

3) **Unified signatures for dependency injection**  
   Many classes accept configuration arrays or value objects, so you can swap
   implementations (Disk, CSV, DB, etc.) without rewriting your core logic.

4) **Behavior as a first-class design tool**  
   Events, actions, and state are not "add-ons." They are embedded in the
   architecture via `Behaves`, `Dispatches`, and the `Behavioral` system.

5) **Interoperability and adaptability**  
   Async utilities, IPC helpers, and the hook/filter system allow you to
   integrate across processes and evolve behavior without editing core files.

This approach is opinionated: the library encourages **composable behaviors,
typed values, and repeatable signatures**, even if that feels more structured
than vanilla PHP at first.

## Core Principles (Minimalism, Modularity, Language Influences)

DevElation follows a **minimal-but-composable** philosophy:

- **Minimalism**: keep each class focused on a single responsibility, but make
  it highly reusable through consistent signatures and typed wrappers.
- **Modularity**: build small modules (Data, System, Services, Cli, Async, IPC)
  that can be adopted independently or combined into a full framework.
- **Language influences**: the chaining style and value objects echo fluent
  patterns found in languages like Ruby (expressive chainable APIs) and Java
  (typed intent and structured data contracts). These influences appear in how
  DevElation prefers explicit types and composable method calls.

DevElation is *not* minimal in features, but it is minimal in **repetition**:
the same patterns are reused everywhere so you learn them once.

## What the Library Is Best Suited For

DevElation is a strong fit when you need:

- **Rapid prototyping with a path to production** (Disk/CSV now, DB later).
- **Event-driven logic** (behaviors, hooks, and filters across your codebase).
- **Data normalization and validation** (Val objects + Schema constraints).
- **CLI tooling and automation** (Console, Args, Tty, Screen, ProgressBar).
- **Cross-process and async workflows** (Async, IPC, Sock, Fork, Thread).
- **Reusable services** with consistent signatures for injection.

## What the Library Is *Not* Best Suited For

Consider other tools when you need:

- A strict, minimal runtime with no object wrappers.
- A conventional MVC framework with minimal abstraction.
- A heavily standardized PSR-only stack with no extra behavior layer.
- Micro-optimized tight loops where object overhead is unacceptable.

DevElation is intentionally **rich**. If you want the absolute smallest
surface area, vanilla PHP (or a narrow framework) may be a better fit.

## Why DevElation Feels Different (Comparisons)

### 1) Vanilla PHP vs DevElation (Typed Values + Events)

Vanilla PHP:

```php
$name = trim(strtolower($name));
if ($name === '') {
    throw new InvalidArgumentException('Missing name');
}
```

DevElation:

```php
use BlueFission\Str;
use BlueFission\Behavioral\Behaviors\Event;

$name = Str::make($name)
    ->trim()
    ->lower()
    ->val();

$string = Str::use();
$string->when(new Event(Event::CHANGE), function () {
    // react to value changes
});
```

Why this matters:
- Values become **observable** and **constrainable**.
- A single style of method chaining replaces repeated raw functions.

### 2) Collections in Frameworks vs DevElation (Val + Arr)

Laravel-ish collections are great, but DevElation adds **type intent** and
**behavior-driven constraints** at the value level:

```php
use BlueFission\Arr;

$ids = Arr::make([1, '2', 3])
    ->unique()
    ->val(); // [1, '2', 3]
```

When you need validation:

```php
use BlueFission\Data\Schema;

$schema = new Schema([
    'id' => ['type' => 'int', 'required' => true],
    'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
]);

$data = $schema->apply(['id' => '42', 'tags' => ['alpha', 5]]);
// $data => ['id' => 42, 'tags' => ['alpha', '5']]
```

### 3) Dependency Injection by Signature (Disk -> DB)

Prototype with disk/CSV, then scale to SQL or NoSQL without rewriting logic.

```php
use BlueFission\Data\Storage\Disk;

$store = new Disk([
    'location' => __DIR__ . '/data',
    'name' => 'users.json',
]);

$store->activate()->read();
$users = (array)($store->contents() ?? []);

$users[] = ['id' => 1, 'name' => 'Ada'];
$store->assign($users)->write();
```

Later, swap to MySQL or SQLite with the same core calls:

```php
use BlueFission\Data\Storage\MySQL;

$store = new MySQL([
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'secret',
    'db' => 'app',
    'table' => 'users',
]);

$store->activate()->read();
$users = (array)($store->contents() ?? []);
```

This is the "unified signature" principle in action.

## Core Patterns You Will See Everywhere

### 1) Value Objects (Val, Str, Num, Arr, Flag, Date)

```php
use BlueFission\Str;

$slug = Str::make('Hello World!')
    ->trim()
    ->lower()
    ->replace(' ', '-')
    ->val();
```

Key concepts:
- `Str::make()` creates a typed object
- `Str::grab()` and `Str::use()` reuse the last value
- Methods call internal `_method` helpers (e.g., `_trim`) with chaining

### 2) Dynamic Objects (Obj)

`Obj` replaces repetitive getters/setters while enforcing types.

```php
use BlueFission\Obj;
use BlueFission\DataTypes;

class User extends Obj
{
    protected $_data = [
        'name' => '',
        'age' => 0,
    ];

    protected $_types = [
        'name' => DataTypes::STRING,
        'age' => DataTypes::INTEGER,
    ];
}

$user = new User();
$user->assign(['name' => 'Ada', 'age' => '42']);
```

### 3) Behaviors and Events

```php
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Data\Graph\Graph;

$graph = new Graph();
$graph->when(new Event(Event::CHANGE), function () {
    // react to graph changes
});
```

Behaviors are consistently named and reused across modules.  
This lets you instrument systems without rewriting core logic.

### 4) Hooks and Filters (DevElation::apply / DevElation::do)

Hooks let you customize behavior **without editing vendor code**.

```php
use BlueFission\DevElation as Dev;

Dev::filter('data.schema.apply', function ($data) {
    $data['source'] = 'injected';
    return $data;
});

Dev::action('data.graph.connect', function ($edge) {
    // log graph changes
});
```

How it works:
- `Dev::apply()` transforms values before they are used
- `Dev::do()` fires actions with no return
- Hook names are auto-generated from class/method if null

This is the core of **monkey patching** in DevElation.  
You can override behavior even when the library is a vendor dependency.

## Services, CLI, Async, and IPC

DevElation includes direct-to-application structures:

- `src/Services` provides injectable service definitions with consistent
  construction patterns.
- `src/Cli` and `src/Cli/Util` offer Console, Args, Cursor, Screen, Tty,
  ProgressBar, and Prompt helpers for CLI scaffolding.
- `src/Async` and `src/IPC` provide asynchronous and inter-process tools.
- `src/Utils/Loader` provides lightweight class discovery and loading to help
  bootstrap scripts or modular toolchains.

These are intentionally shallow abstractions so you can scaffold quickly
and replace components as you mature your application.

## Coding Standards and Style Guidance

DevElation has consistent coding standards that are visible in the core code and
documented in `coding_standards.md`. The key expectations are:

- Prefer `Val`, `Str`, `Arr`, `Num`, `Flag`, and `Date` helpers over raw PHP
  functions when you are working inside DevElation code.
- Use `Obj` field access (`assign`, property access, constraints) instead of
  manual getters/setters where possible.
- Trigger behavior events on state changes (see `Event::CHANGE`, `Event::SENT`,
  `Event::ITEM_ADDED` patterns in the Cli and Data modules).
- Keep method signatures consistent across parallel classes to support
  easy dependency injection and swapping.

If you contribute to this library or derive from it, write in that style to
keep the codebase consistent and readable for the rest of the team.

## Quirks to Know (So You Don't Get Surprised)

- `Val::use()` and `Val::grab()` work off the **last used static value**, which
  can feel magical. Use them intentionally.
- `Obj` exposes value objects internally; depending on `_exposeValueObject`,
  you might get an `IVal` or a raw value. Be explicit when that matters.
- Behaviors require intentional wiring: events will only fire if a class uses
  `Behaves` or `Dispatches`, and handlers are attached.
- Hooks (`DevElation::apply` / `DevElation::do`) are **disabled by default**
  until `DevElation::up()` is called.

These quirks are not bugs; they are part of the library's flexibility.

## Influence Notes (Laravel + WordPress)

DevElation is not a clone of any other framework, but it **intentionally borrows
ideas**:

- **Laravel** influences are most visible in the *service* layer and in the
  utility surface where fluent helpers and convenience APIs are favored.
- **WordPress** influences are most visible in the **hook system**
  (`DevElation::apply` and `DevElation::do`), which mirrors filters/actions and
  supports customization without editing vendor code.

These inspirations help DevElation stay flexible while keeping a clear identity
and consistent engineering style.

## Opportunities to Exploit the Patterns

Here are some common ways teams use DevElation effectively:

- **Instrumentation-first design**: attach `Event::CHANGE` handlers early.
- **Schema-first data pipelines**: validate inputs at module boundaries.
- **Swap storage backends** using `Storage` subclasses without changing code.
- **Apply hooks** to add caching, logging, or feature flags without edits.
- **Async workflows** using `Async::resolve()` / `Promise` patterns.

## Summary

DevElation is designed to make **everything composable**:
values, objects, behavior, services, and data flow. It introduces a consistent
language around events, types, and hooks that does not exist in vanilla PHP.

If you lean into those patterns, you gain:
- faster prototyping,
- stronger validation,
- cleaner substitution of dependencies,
- and safer extensibility in real-world systems.
