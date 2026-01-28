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

## PHP Basics vs DevElation (Primitives, Scalars, and Objects)

PHP gives you **primitives/scalars** like:

- `int`, `float` (or `double`)
- `string`
- `bool`
- `array`

These are powerful, but they are **not objects**. That means you cannot attach
constraints, events, or consistent method chains to them without extra code.

DevElation wraps those primitives in **value objects** so they can be:

- validated or normalized before use,
- chained with consistent method names,
- observed via events and behaviors.

Example: plain scalar vs value object

```php
// vanilla
$age = (int) $age;
if ($age < 0) {
    $age = 0;
}

// DevElation
use BlueFission\Num;

$age = Num::make($age)->min(0)->val();
```

The DevElation version is not "magic." It is a **type-aware object** that
behaves predictably and can be extended through hooks and constraints.

## Software Development Basics (In DevElation Terms)

If you are new to software development, keep these ideas in mind:

- **Data** should be validated and normalized early (before it spreads).
- **Logic** should be modular and testable (small pieces, well-defined inputs).
- **Side effects** should be isolated (file writes, network calls, output).

DevElation supports these basics by:

- using **Schema** and **Val** objects for early validation,
- providing **Services** and **Storage** classes with predictable signatures,
- using **Events** and **Hooks** to connect modules without hard coupling.

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

### 2a) DevElation Collection vs Laravel Collection

Laravel collections are great for fluent `map`, `filter`, and `reduce` flows.
DevElation's `Collection` is intentionally **lighter** and focused on storage
and simple access, while the `Arr` and `Val` classes handle transformations.

Laravel-style:

```php
$names = collect(['Ada', 'Grace'])
    ->map(fn($name) => strtoupper($name))
    ->all();
```

DevElation-style:

```php
use BlueFission\Collections\Collection;
use BlueFission\Str;

$names = new Collection(['Ada', 'Grace']);
$upper = [];
foreach ($names as $name) {
    $upper[] = Str::make($name)->upper()->val();
}
```

This split is intentional: **Collections store**, value objects **transform**.

### 2b) DevElation Arr vs PHP Array Helpers

PHP arrays come with many helpers (`array_map`, `array_filter`, etc.).
DevElation wraps arrays into `Arr` so you get a consistent fluent interface.

```php
use BlueFission\Arr;

$list = Arr::make(['b', 'a', 'a'])
    ->unique()
    ->sort()
    ->val();
```

This keeps array logic **in the object** rather than scattered across
global functions.

### 2c) Numbers and Arrays: Normalization Differences

DevElation normalizes **numbers** and **arrays** explicitly:

- `Num` auto-detects **integer vs double** during `cast()`.
- `Arr` can tell you **indexed vs associative** arrays.

```php
use BlueFission\Num;
use BlueFission\Arr;

$value = Num::make('42.0')->cast();
// $value is a number object; internal type becomes integer when appropriate

$data = Arr::make(['a' => 1, 'b' => 2]);
$data->isAssoc();   // true
$data->isIndexed(); // false
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

### 2a) Constraint-Based Pre-Validation

DevElation uses constraints to normalize and validate values **before** they
become part of an object or schema.

```php
use BlueFission\Str;

$name = Str::make('   ');
$name->constraint(function (&$value): bool {
    $value = Str::trim($value);
    return $value !== '';
});

$name->val('   ');
// value is trimmed and normalized before use
```

At the schema level, constraints can **reject** invalid data and surface
errors without crashing the system.

You can also attach constraints to `Obj` fields by exposing the underlying
value objects:

```php
$user = new User();
$user->exposeValueObject(true);
$user->name->constraint(function (&$value): bool {
    $value = Str::trim($value);
    return $value !== '';
});
$user->exposeValueObject(false);
```

### 2b) Looping Directly on Objects

Many DevElation objects implement `IteratorAggregate` or `ArrayAccess`,
so you can loop over them directly.

```php
use BlueFission\Arr;
use BlueFission\Collections\Collection;

$arr = Arr::make([1, 2, 3]);
foreach ($arr as $value) {
    // $value is each item
}

$collection = new Collection(['a', 'b']);
foreach ($collection as $value) {
    // $value is each item
}
```

### 2c) Grouping and Tagging Values

DevElation lets you tag value objects and work with typed groups.

```php
use BlueFission\Num;
use BlueFission\Collections\Group;

$score = Num::make(98)->tag('scores');
$penalty = Num::make(3)->tag('scores');

$group = new Group();
$group->type(Num::class);
$group->add($score);
$group->add($penalty);
```

This is a lightweight way to group related values for later processing.

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

## Vanilla PHP Class vs DevElation Obj (Complexity Comparison)

Vanilla PHP class:

```php
class User
{
    private string $name = '';
    private int $age = 0;

    public function setName(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Name required');
        }
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

DevElation-style:

```php
use BlueFission\Obj;
use BlueFission\DataTypes;
use BlueFission\Str;

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
$user->name = Str::make(' Ada ')->trim()->val();
```

The DevElation version is **shorter** but still **validated** and **typed**.
It also becomes event-capable by default because `Obj` uses `Behaves`.

## Async and Events (What Vanilla PHP Lacks)

Vanilla PHP does not give you:

- a native event bus,
- structured behaviors and states,
- first-class async helpers.

DevElation provides these in `Behavioral`, `Async`, and `IPC` modules.

```php
use BlueFission\Async\Promise;

$promise = new Promise(function ($resolve) {
    $resolve('done');
});

$promise->then(function ($value) {
    // async-style continuation
});
```

Events and behaviors are equally central:

```php
use BlueFission\Behavioral\Behaviors\Event;

$obj->when(new Event(Event::CHANGE), function () {
    // react to updates without hard-coding dependencies
});
```

## MVC, MVVC, and Other Architectures

DevElation does not force a single architecture. You can build:

- **MVC** by pairing Models (Data/Schema) with Controllers (Services) and
  Views (HTML tools or templating).
- **MVVC** (or MVVM) by using Services as View-Models and `Behavioral`
  events as the binding layer.

Because the library is **modular**, you can adopt it in part or as a base for
your own framework conventions.

## Embedding Complexity in Fewer Lines

DevElation embeds complexity in **components**, not in your application code.

Example: updates without rewriting logic

```php
use BlueFission\DevElation as Dev;
use BlueFission\Data\Storage\Disk;

Dev::filter('data.storage.write', function ($payload) {
    $payload['checksum'] = hash('sha256', json_encode($payload));
    return $payload;
});

$store = new Disk(['location' => __DIR__, 'name' => 'cache.json']);
$store->assign(['status' => 'ok'])->write();
```

You just added checksum behavior without editing `Disk` itself.

Single-signature swap example:

```php
// prototype
$store = new \BlueFission\Data\Storage\Disk([
    'location' => __DIR__,
    'name' => 'cache.json',
]);

// later
$store = new \BlueFission\Data\Storage\MySQL([
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'secret',
    'db' => 'app',
    'table' => 'cache',
]);

// same usage in both cases
$store->activate()->read();
$store->assign(['status' => 'ok'])->write();
```

## The Library as a Way of Thinking

DevElation is meant to be the **seed** for all Blue Fission systems. The value
objects, events, hooks, and unified signatures make it ideal for:

- AI tooling (deterministic data preparation + behavioral instrumentation)
- Interoperability layers (IPC + async + hooks)
- Web and CLI applications (shared Data/Schema/Services patterns)
- High configurability (apply/do hooks and dependency injection by use case)

The "way of thinking" is simple:

- **Define consistent signatures** so components can be swapped.
- **Move logic into behaviors** so state is visible and observable.
- **Use types and constraints** to keep data reliable without extra code.
- **Let objects communicate** through events, hooks, and tagged groupings.

That combination makes DevElation unusually flexible: you can prototype fast,
scale later, and customize even when it's a vendor dependency.

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
