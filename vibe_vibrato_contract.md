# Vibe and Vibrato DevElation Capability Contract

This document is the DevElation-owned contract for Keryx discussion room `#7`,
GitHub issue `BlueFissionTech/develation#100`, Vibe issue
`BlueFissionTech/vibe#5`, Vibrato issue `BlueFissionTech/vibrato#4`,
and Reactor issue `BlueFissionTech/reactor#8`.

The goal is to let Vibe specify a stable language construct and let Vibrato
implement it without guessing DevElation semantics. DevElation owns the PHP
capability inventory, signatures, hook behavior, event behavior, mutability
rules, and compatibility boundaries. Vibe owns final syntax. Vibrato owns the
runtime adapter and conformance tests. Reactor owns only UI and binding effects
that arise from the language surface.

## Contract Gate

No implementation PR should merge across repo boundaries until the relevant
capability has a matrix row with:

- DevElation feature
- Candidate Vibe construct
- Runtime adapter method
- Expected result
- Error behavior
- Hook or event behavior
- Conformance fixture
- Owning issue

This first version is a baseline. It is intentionally explicit about stable
DevElation behavior and marks Vibe syntax as candidate syntax until the Vibe
spec repo accepts it.

## Type Surface

Vibrato should map Vibe type names to DevElation classes through a registry
rather than hard-coding class names inside parser logic.

| Vibe type | DevElation class | Native value returned by default | Notes |
| --- | --- | --- | --- |
| `value`, `val` | `BlueFission\Val` | mixed | Generic wrapper and fallback type. |
| `text` | `BlueFission\Str` | string | String transforms, regex helpers, bool parsing. |
| `number` | `BlueFission\Num` | int or float | Arithmetic, formatting, base conversion. |
| `flag` | `BlueFission\Flag` | bool | Boolean parsing and conversion. |
| `list` | `BlueFission\Arr` | array | Array helpers, path reads, static count support. |
| `date` | `BlueFission\Date` | date/time scalar | Formatting, timestamp, interval helpers. |
| `object` | `BlueFission\Obj` | array or object projection | Dynamic fields, typed field access, JSON output. |
| `macro` | `BlueFission\Func` | callable result | Callable wrapper for macro/function surfaces. |

The current DevElation parser registers these defaults in
`BlueFission\Parsing\Registry\DatatypeRegistry::registerDefaults()`.

## Invocation Rules

### Instance Helpers

DevElation value objects expose public methods and protected-style helper
methods whose public name omits the leading underscore. For example,
`BlueFission\Str::_trim()` is invoked as `$str->trim()` through
`Val::__call()`.

Contract:

- A helper method named `_name` is callable as `name`.
- Successful dynamic helper calls trigger `Event::ACTION_PERFORMED`.
- Missing instance helper calls log an error, trigger
  `Event::ACTION_FAILED` and `Event::ERROR`, and return `null`.
- Mutating helpers should return an `IVal` instance when continued chaining is
  expected.
- Terminal helpers may return scalars, arrays, or objects.

### Static Helpers

`Val::__callStatic()` treats the first argument as the wrapped value and invokes
the matching underscored helper on a temporary instance.

Contract:

- `Str::trim(' value ')` is equivalent to `Str::make(' value ')->trim()->val()`.
- If a static helper returns `IVal`, the static call returns `IVal::val()`.
- If a static helper returns a scalar, array, object, or `null`, that value is
  returned directly.
- Missing static helpers log an error and return `false`.
- `Arr::count($value)` is explicitly supported and returns the PHP-style count
  of the wrapped array while preserving the DevElation hookable helper surface.

## Object-Preserving Chained Transforms

Vibrato should preserve wrapper identity across chain steps until a terminal
value is required.

Contract:

- Start each typed chain with `DatatypeRegistry::get($type)::make($value)`.
- For each method step, call the public helper name. The method may resolve to
  a public method or to an underscored helper through `Val::__call()`.
- If the step returns `IVal`, continue with that returned object.
- If the step returns a scalar or array and more steps remain, Vibrato must
  either recast using the active type or raise a deterministic diagnostic.
- Final Vibe assignment should store native values unless the construct
  explicitly asks to preserve the object wrapper.
- Dotted path reads from arrays/objects are terminal accessors unless the Vibe
  spec casts the read value into a new typed chain.

Example candidate behavior:

```vibe
{#let title:text=' hello world '}
{=title:text -> $.trim().capitalize() -> displayTitle}
```

Expected adapter sequence:

1. `Str::make(' hello world ')`
2. `trim()` returns `Str`
3. `capitalize()` returns `Str`
4. assignment stores `displayTitle = 'Hello world'`

## Hook Contract

`BlueFission\DevElation` is the global hook gateway. It is inactive by default.

| API | Signature | Behavior |
| --- | --- | --- |
| `DevElation::up()` | `(): void` | Activates filters, actions, and listeners. |
| `DevElation::down()` | `(): void` | Deactivates hook execution without clearing registrations. |
| `DevElation::filter()` | `(string $name, callable $fn, int $priority = 10): void` | Registers a filter by name. Priorities are sorted by key. |
| `DevElation::apply()` | `(?string $name = null, mixed $value = null): mixed` | Applies registered filters when active, otherwise returns the original value. |
| `DevElation::action()` | `(string $name, callable $fn, int $priority = 10): void` | Registers side-effect-only actions. |
| `DevElation::do()` | `(?string $name = null, array $args = []): void` | Runs matching actions when active. |
| `DevElation::listen()` | `(string $eventOrBehavior): void` | Creates a listener bucket. |
| `DevElation::subscribe()` | `(callable|object $subscriber, string $eventOrBehavior): void` | Adds a subscriber to a listener bucket. |
| `DevElation::trigger()` | `(string $eventName, array $args = []): void` | Calls registered listeners when active. |

When the hook name is `null`, DevElation auto-generates a lower-case
`Class.method` hook name from the caller. Existing parsing registries use
explicit `_in`, `_out`, `_before`, and `_after` hook names. Vibrato should not
invent additional implicit hook names without recording them in the matrix.

## Behavior and Event Contract

DevElation uses `Behavior`, `Event`, `Action`, `State`, `Meta`, `Dispatches`,
and `Behaves` for evented components.

Core dispatcher contract:

- `behavior($behavior, ?callable $callback = null)` registers a behavior and
  optional handler.
- `when($behavior, callable $callback)` is an alias for `behavior`.
- `dispatch($behavior, $args = null)` normalizes strings into `Behavior`.
- Non-array non-`Meta` args are wrapped as an array before handler dispatch.
- `Meta` exposes `when`, `info`, `data`, and `src`.
- Constructors using `Dispatches` trigger `Event::LOAD`.
- Destructors using `Dispatches` trigger `Event::UNLOAD`.

Core value-object events:

- `Val::val($value)` triggers `Event::CHANGE` when the value changes.
- `Val::__call()` triggers `Event::ACTION_PERFORMED` on successful helper calls.
- Missing instance helpers trigger `Event::ACTION_FAILED` and `Event::ERROR`.

Important event names for Vibe/Vibrato:

| Kind | Constants |
| --- | --- |
| Lifecycle | `OnLoad`, `OnUnload`, `OnInitialized`, `OnFinalized`, `OnStarted`, `OnStopped`, `OnComplete` |
| Mutation | `OnChange`, `OnCreated`, `OnRead`, `OnUpdated`, `OnSaved`, `OnDeleted`, `OnClearData` |
| Result | `OnSuccess`, `OnFailure`, `OnProcessed`, `OnActionPerformed`, `OnActionFailed` |
| Connectivity | `OnConnected`, `OnDisconnected`, `OnSent`, `OnReceived`, `OnBlocked` |
| Error | `OnError`, `OnException` |
| State | `OnStateChanged` |

## Error and Diagnostic Contract

Vibrato diagnostics should include:

- Vibe construct or tag name
- DevElation class and method attempted
- Input value type
- Arguments
- Hook name, when a hook was active
- Original exception message or DevElation fallback result

Stable error behavior:

| Condition | DevElation behavior | Vibrato expectation |
| --- | --- | --- |
| Missing instance helper | Logs, triggers `OnActionFailed` and `OnError`, returns `null` | Diagnostic with class and method. |
| Missing static helper | Logs and returns `false` | Diagnostic unless construct explicitly accepts false. |
| Invalid behavior type | Throws `InvalidArgumentException` | Preserve exception type in diagnostic. |
| Unimplemented behavior | Throws `NotImplementedException` | Preserve behavior name and target class. |
| Unsupported parser cast | Throws `Exception` with cast name | Surface as language type error. |
| Unresolved source file | Throws `RuntimeException` with source path | Surface as source-resolution error. |
| Append/push to invalid type | Throws `Exception` | Surface as assignment-shape error. |

## JSON and Storage Contract

JSON support is distributed across values, parser typing, HTTP helpers, and
storage drivers.

Stable surfaces:

- `Arr::toJson(): string`
- `Obj::toJson(): string`
- `HTTP::jsonEncode($value): string`
- Parser typed JSON attributes such as `{#let settings:json='{\"theme\":\"dark\"}'}`
- `Disk`, `Cookie`, `Session`, and `Memory` storage drivers that decode JSON
  content into data fields where supported.
- `Data\Storage\Structure\MysqlStructure::json($name)` for schema generation.

Vibrato should treat JSON read/write as two separate concerns:

- Language-level JSON literals are owned by Vibe and Vibrato parser behavior.
- DevElation storage JSON read/write is owned by DevElation storage contracts.

## Format Ownership and Retry Contract

The word `format` has two different meanings in this integration.

DevElation owns value formatting helpers only:

- `Num::format($format)`, `Num::precision($precision)`,
  `Num::decimal($decimal)`, and `Num::thousands($thousands)`
- `Date::format($format)`
- String rendering through `__toString()` on value objects

Vibe and Vibrato own any language-level `#format` construct, prompt retry
policy, tool-feedback loop, and generated-content validation. DevElation does
not own prompt retries or output repair. If `#format` calls into a DevElation
value helper, the DevElation side of the contract is only the deterministic
helper result, thrown PHP exception, or documented fallback return.

Vibrato diagnostics for `#format` should identify whether failure came from
the Vibe formatting construct, the generator/tool feedback layer, or a
DevElation value helper. Only the final case belongs in DevElation issue
tracking.

## Capability Matrix

| DevElation feature | Candidate Vibe construct | Runtime adapter method | Expected result | Error behavior | Hook/event behavior | Conformance fixture | Owning issue |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Generic value lifecycle | `{#let value:val='x'}` then `{=value:val -> $.snapshot().val('y').delta() -> delta}` | `Val::make`, `snapshot`, `val`, `delta`, `clear`, `reset` | Native value or delta | Missing helper returns `null`; invalid calls are diagnostics | `OnLoad`, `OnChange`, `OnActionPerformed` | `val_lifecycle.vibe` | develation#100, vibrato#4 |
| Static helper dispatch | `{=text.trim(' value ') -> output}` or accepted Vibe equivalent | `Val::__callStatic` on datatype class | Native value | Missing static helper returns `false` | No instance event guaranteed after object destruction | `static_helper_dispatch.vibe` | develation#100, vibe#5, vibrato#4 |
| String transforms | `{=title:text -> $.trim().lower().capitalize() -> display}` | `Str` helpers: `trim`, `lower`, `upper`, `capitalize`, `snake`, `camel`, `slugify`, `replace`, `replacePattern` | string | Regex errors should become diagnostics | Change event for mutating helpers | `str_transforms.vibe` | develation#100, vibrato#4 |
| String matching | `{=title:text -> $.contains('blue') -> hasBlue}` | `Str::contains`, `startsWith`, `matches`, `matchPattern`, `similarityTo` | bool, array, or float | Invalid regex is diagnostic | `OnActionPerformed` on valid helper | `str_matching.vibe` | develation#100, vibrato#4 |
| Number arithmetic | `{=amount:number -> $.add(5).multiply(2).round(0) -> total}` | `Num::add`, `sub`, `multiply`, `divide`, `round`, `abs`, `sq`, `sqrt`, `pow`, `log`, `exp` | number | Divide-by-zero behavior must be tested before final grammar guarantee | Mutating helpers change wrapped value | `num_arithmetic.vibe` | develation#100, vibrato#4 |
| Number formatting | `{=amount:number -> $.precision(2).decimal('.').thousands(',') -> label}` | `Num::precision`, `decimal`, `thousands`, `format`, `__toString` | formatted string at terminal render | Bad format string follows PHP formatting behavior | Mutating format settings affect string output | `num_formatting.vibe` | develation#100, vibe#5 |
| Base conversion | `{=bits:number -> $.bin('101010') -> decimal}` | `Num::bin`, `hex`, `oct`, `rom`, `dec` | number or string depending call direction | Invalid roman/base input should be diagnostic after fixture proof | `OnActionPerformed` | `num_base_conversion.vibe` | develation#100, vibrato#4 |
| Boolean parsing | `{=flag:flag -> $.parseBool(true) -> enabled}` | `Flag::parseBool`, `toBool`, `toInt`, `flip`, `isTrue`, `isFalse` | bool or int | Unknown token returns default | `OnChange` for mutating helpers | `flag_parse.vibe` | develation#100, vibrato#4 |
| Array count and size | `{=items:list -> $.count() -> itemCount}` and static `Arr::count($items)` | `Arr::count`, `Arr::size`, `Arr::__callStatic('count')` | int | Non-array returns `0` for count | Hookable via helper dispatch | `arr_count.vibe` | develation#100, vibrato#4 |
| Array lookup | `{=items:list -> $.hasKey('id') -> hasId}` | `Arr::has`, `hasValue`, `contains`, `hasKey`, `getPath`, `arrayAtPath` | bool, mixed, or array | Missing path returns default for `getPath` | `OnActionPerformed` | `arr_lookup.vibe` | develation#100, vibrato#4 |
| Array transforms | `{=items:list -> $.merge(extra).unique().values() -> merged}` | `Arr::merge`, `append`, `prepend`, `remove`, `delete`, `unique`, `iUnique`, `keys`, `values`, `intersect`, `diff`, `flip` | array or `Arr` before final assignment | Invalid non-array inputs are no-op or fallback per helper | Mutators trigger `OnChange` through `alter` paths where implemented | `arr_transforms.vibe` | develation#100, vibrato#4 |
| Object fields | `{=profile:object -> $.field('name','Ada').toArray() -> profileData}` | `Obj::field`, magic get/set, `assign`, `data`, `toArray`, `toJson`, `exposeValueObject` | array, JSON, or field value | Non-associative assign is rejected by current tests | Magic setters alter internal field values | `obj_fields.vibe` | develation#100, vibrato#4 |
| Date values | `{=now:date -> $.format('Y-m-d') -> today}` | `Date::now`, `timestamp`, `format`, `date`, `time`, `diff` | string, int, or float | Invalid date strings follow PHP DateTime parsing behavior | `OnChange` on value changes | `date_format.vibe` | develation#100, vibrato#4 |
| DevElation hooks | Vibe hook declaration to be specified by vibe#5 | `DevElation::filter`, `apply`, `action`, `do`, `listen`, `subscribe`, `trigger` | filtered value or side effect | Inactive hooks no-op and return original value | Hook names `_in`, `_out`, `_before`, `_after` where used | `hooks_contract.vibe` | develation#100, vibe#5, vibrato#4 |
| Behavior events | Vibe event binding to be specified by vibe#5 | `behavior`, `when`, `handler`, `dispatch`, `perform`, `is`, `can` | side effects or state changes | Invalid behavior throws typed exception | Standard `Event`, `Action`, `State`, `Meta` payloads | `behavior_events.vibe` | develation#100, vibe#5, reactor#8 |
| Parser datatypes | Existing typed constructs such as `{#let name:text='Ada'}` | `DatatypeRegistry::registerDefaults`, `Element::resolveCastClass` | typed wrapper then native assignment | Unsupported cast throws | Parser uses `Dev::apply('_in')` and `Dev::apply('_out')` | `typed_let_eval.vibe` | vibe#5, vibrato#4 |
| Parser flow | `{#if}`, `{#each}`, `{#while}`, `{#until}`, `{#include}`, `{#import}`, `{#macro}`, `{#invoke}` | Parsing elements and registries | rendered output or scope mutation | Element-specific exceptions | Evaluator hooks `_before` and `_after` | `parser_flow.vibe` | vibe#5, vibrato#4 |
| JSON literals | `{#let settings:json='{\"theme\":\"dark\"}'}` | `Element::parseAttributeValue` and evaluator parameter parsing | array/object literal | Invalid JSON should become parse diagnostic | Parser hook surface only | `json_literals.vibe` | vibe#5, vibrato#4 |
| Data schema | Vibe data contract syntax to be specified by vibe#5 | `Schema`, `FieldDefinition`, storage structures | validated/cast arrays and schema definitions | Constraint failures expose validation errors | Storage behaviors for persistence paths | `schema_contract.vibe` | develation#100, vibe#5 |
| Storage read/write | Vibe storage syntax to be specified after contract matrix | `Storage`, `Disk`, `Memory`, `Session`, `Cookie`, `SQLite`, `MySQL`, `Mongo` | persisted content or field arrays | Driver-specific connection errors | `StorageAction`, `StorageEvent`, `StorageState` | `storage_contract.vibe` | develation#100, vibrato#4 |
| Queues | Vibe queue syntax to be specified after runtime scope | `Queue`, `SplQueue`, `SplPriorityQueue`, `FileQueue`, `DiskQueue`, `MemQueue`, `DBQueue` | enqueued/dequeued items | Empty dequeue behavior is queue-specific | Queue operations are not globally evented unless wrapped | `queue_contract.vibe` | develation#100, vibrato#4 |
| Network HTTP | Vibe network syntax should remain opt-in | `HTTP`, `HTTPClient`, `Curl`, `Request`, `Response` | response body, decoded body, or request metadata | Network tests must stay opt-in | Connection events where dispatcher-backed | `http_contract.vibe` | develation#100, vibrato#4 |
| Services | Vibe service syntax to be specified only after language contract | `Application`, `Service`, `Request`, `Response`, `Gateway`, `Mapping`, `Credentials`, `Authenticator` | routed response or service result | Auth/routing failures remain service diagnostics | Service dispatch events and message completion | `services_contract.vibe` | develation#100, reactor#8 |
| System and async | Vibe system execution must be explicit and sandbox-aware | `System`, `Process`, `CommandLocator`, `Async`, `Promise`, `Shell`, `Remote`, `Fork`, `Heap` | process output, promise result, or queued side effect | Invalid command throws; OS/process limits are diagnostics | `Action::RUN`, process start/stop/error events | `system_async_contract.vibe` | develation#100, vibrato#4 |
| Security helpers | Vibe security helpers should be pure functions unless configured otherwise | `Security\Hash` | hash, HMAC, checksum, content id | Unsupported algorithm throws or returns helper-specific failure | No global event guarantee | `hash_contract.vibe` | develation#100, vibrato#4 |
| Reactor binding impact | Vibe/Reactor construct to be specified by reactor#8 | Reactor binding adapter, not DevElation runtime | UI binding metadata | Reactor owns UI diagnostics | Lifecycle mapping must not redefine DevElation events | `reactor_binding_contract.vibe` | reactor#8 |

## Open Decisions for Vibe

Vibe issue `#5` should decide:

- Whether DevElation calls use a namespace form such as `dev.text.trim(...)` or
  typed chain form such as `value:text -> $.trim()`.
- How to explicitly request object preservation versus native-value assignment.
- How hook declarations are represented in source.
- Whether system/network/storage capabilities require opt-in declarations.
- How conformance fixtures are named and shared with Vibrato.

## Open Decisions for Vibrato

Vibrato issue `#4` should decide:

- The adapter interface that maps Vibe constructs to DevElation classes.
- The diagnostic envelope shape for DevElation exceptions and fallback returns.
- How to isolate optional network, process, and storage tests.
- How to verify hook order and event payloads without vendor edits.
- How to load DevElation from Composer in production and from a local path in
  cross-repo conformance tests.

## Open Decisions for Reactor

Reactor issue `#8` should decide:

- Which Vibe constructs require frontend binding metadata.
- Which lifecycle events map to UI binding lifecycle.
- Which DevElation events remain backend-only.
- How Reactor reports unsupported constructs without taking ownership of the
  language or runtime adapter.

## Acceptance Criteria for Issue 100

- The contract matrix exists and is linked from `README.md`.
- Vibrato's asks are answered: capability inventory, signatures, chained
  transform semantics, hook/event payloads, JSON helpers, and `#format`
  ownership boundaries.
- Every row names an owning issue.
- No new runtime behavior is introduced by this document.
- Follow-up implementation work remains in the owning repos.
