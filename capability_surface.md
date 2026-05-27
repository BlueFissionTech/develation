# DevElation Capability Surface

This document describes DevElation's own capability surface for general PHP use.
It is a guide for keeping public helpers, primitives, data tools, behaviors, and
services coherent as the library evolves.

## Design Principles

- DevElation owns its primitive wrappers, data abstractions, behavior system,
  parsing utilities, service helpers, and extension points.
- Consumer applications and libraries adapt to DevElation through public APIs,
  inheritance, composition, hooks, and documented contracts.
- Feature requests can inform the roadmap, but accepted capabilities must make
  sense for DevElation as a general-purpose library.
- Public helpers should behave consistently across instance and static usage
  whenever the underlying class supports dynamic helper dispatch.
- Optional integrations should remain opt-in and should not make clean
  installations depend on external services.

## Primitive And Dynamic Helpers

The primitive wrapper classes provide object-oriented helpers around native PHP
values while preserving a simple path back to native data through `val()`.

| Surface | Primary class | Responsibility |
| --- | --- | --- |
| Generic values | `BlueFission\Val` | Base wrapper behavior, dynamic helper dispatch, value access, diagnostics, hooks. |
| Strings | `BlueFission\Str` | String transforms, normalization, formatting, matching, and repeat helpers. |
| Numbers | `BlueFission\Num` | Numeric operations, formatting, conversion, and comparison helpers. |
| Flags | `BlueFission\Flag` | Boolean parsing, conversion, and boolean-state helpers. |
| Arrays | `BlueFission\Arr` | Array traversal, key/value checks, merging, mapping, filtering, counting, and ordering helpers. |
| Objects | `BlueFission\Obj` | Dynamic fields, nested value access, object projection, and structured value helpers. |
| Dates | `BlueFission\Date` | Date/time formatting, timestamp handling, intervals, and calendar helpers. |

Dynamic helper conventions:

- An underscored helper such as `_trim()` is exposed as `trim()` through the
  value object's dynamic call path.
- Static helper calls use the first argument as the wrapped value when the class
  supports `Val::__callStatic()`.
- Instance helpers that mutate wrapper state should return the wrapper when
  continued chaining is expected.
- Terminal helpers may return native scalars, arrays, objects, or `null` when
  that is the documented result.

## Hooks And Behaviors

DevElation classes may expose hook points through the library's filtering and
action mechanisms. Hook use should remain predictable:

- Hooks should be inactive unless a caller registers one.
- Inputs and outputs should be explicit enough for callers to understand what
  can be altered.
- Helper-specific hooks should preserve the helper's documented return type.
- Behavior events should report meaningful success, failure, and error states
  without forcing callers to handle events for ordinary use.

The behavior surface includes `Behavior`, `Event`, `Action`, `State`, `Meta`,
and the related traits that make event-driven components consistent across the
library.

## Data And Service Surface

DevElation data classes should expose consistent, substitutable APIs where
practical. File, directory, database, queue, cache, session, and network helpers
should avoid surprising side effects and should document whether an operation
creates, mutates, reads, or only checks a target.

General expectations:

- Existence and reachability checks should be safe for missing targets.
- Constructors and metadata lookups should avoid creating external resources
  unless the class explicitly documents that behavior.
- Optional service integrations should remain disabled in default test runs.
- Shared behavior should prefer existing DevElation collection, filter, object,
  and service utilities over one-off native-only implementations.

## Parsing, HTML, System, And Security

Parsing and templating components should document their supported grammar,
scoping behavior, and error handling in DevElation-owned terms. HTML utilities
should remain focused on generating predictable markup. System helpers should
avoid OS-specific assumptions where portable PHP alternatives are available.
Security helpers should keep hashing, IDs, signatures, and validation behavior
explicit and covered by tests.

## Roadmap Gate

New public capabilities should meet these gates before they are considered
stable:

- The behavior is useful to DevElation as a standalone PHP library.
- The public method name, arguments, return value, and side effects are
  documented or covered by tests.
- Instance and static helper behavior is consistent where both forms are
  supported.
- Hooks, filters, and events are either absent by design or intentionally
  specified.
- Optional integrations do not make clean installs or default test runs depend
  on external services.
