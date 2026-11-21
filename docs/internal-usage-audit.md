# Internal usage audit: file and SQLite boundaries

This is an evidence-based inventory for issue #302. It records current
conventions and candidate corrections; it does not change a public contract.
`AGENTS.standards.md` remains the coding baseline. File I/O and SQLite calls
are native implementation boundaries, not candidates for mechanical wrapping.

## FileSystem

| Evidence | Current convention | Assessment |
| --- | --- | --- |
| `src/Data/FileSystem.php` constructor and `loadInfo()` | `Val`, `Arr`, and `Str` classify configuration and compose path metadata. | Keep the helper surface for value operations; preserve accepted config shapes. |
| `open()`, `_read()`, `_write()`, `_delete()` | Stateful operations set status and publish behavioral events with `Meta`, while opening, reading, writing, locking, and deleting use PHP file APIs. | Keep native file APIs at the I/O boundary and preserve the existing event and fluent object behavior. |
| `fileExists()`, `directoryExists()`, `isReadable()`, `isWritable()`, `fileContents()` | Static path probes avoid constructing stateful storage. | Keep one-shot probes static. A bounded noncreating read is tracked separately in #299; do not silently change `fileContents()` semantics here. |
| `lines()` and `entries()` | `Str::split()` and `Arr::sort()` shape returned values after native file/directory access. | Preserve array return shapes and sorted entries; wrappers around the underlying I/O would hide failure and resource semantics. |

Candidate follow-up: in a bounded-read implementation, use existing `Str`
and `Num` helpers for byte-string length, substring checks, and chunk-size
selection, while keeping `fopen`/`fread`/`fstat` native. Benefit: consistent
library usage without hiding the stream boundary. Risk: `Str::len()` must
remain byte-oriented and helper dispatch must not alter the bound. Focused
tests: exact and over-limit reads, multibyte bytes, missing and non-regular
targets, and unchanged legacy `fileContents()` behavior. The review of #299
owns that implementation, not this audit.

## SQLiteScaffold

| Evidence | Current convention | Assessment |
| --- | --- | --- |
| `src/Data/Storage/Structure/SQLiteScaffold.php` `create()` and `delete()` with a supplied `SQLiteLink` | Both borrow an already-open link, call `query()`, and publish `Event::CREATED`/`DELETED` or failure with `Action` and `Meta` containing entity and operation. | Preserve link ownership and captureable events; these are reusable behavioral conventions. |
| `tests/Data/Storage/Structure/SQLiteScaffoldTest.php` | Tests verify target isolation, quiet borrowed calls, same connection after use, and host `BEGIN`/`ROLLBACK` ownership. | Retain these as compatibility checks for any schema change. |
| `create()`/`delete()` without a supplied link | Legacy path constructs `SQLite` with an implicit location and prints status. | A quiet explicit context is a separate public capability (#300); do not silently redirect the legacy path in an audit. |
| `alter()` | Public method has no implementation or visible failure. | Treat as a documented gap for #300, not as successful schema work or a reason for a broad rewrite. |

Candidate follow-up: after #303 settles link open/error semantics, #300 can
define explicit, quiet schema execution with an inspectable outcome. Benefit:
callers can distinguish an applied change from a failed or unsupported one
without scraping stdout. Risks: return and event shape, DDL idempotency,
transaction ownership, SQLite's limited `ALTER`, and compatibility of the
legacy path. Focused tests: repeated create/delete, mismatched schema,
unsupported alter, no stdout, failure event metadata, host rollback and
post-failure reuse, and isolation between database files. No new hook is
proposed without a concrete extension use case.

## Arr and Obj behavioral boundaries

| Evidence | Current convention | Assessment |
| --- | --- | --- |
| `src/Arr.php` `_slice()` / `_splice()` | `array_slice()` produces a new `Arr`, while `array_splice()` changes a copy and passes it through `alter()`. | Native array functions are appropriate inside the primitive. Preserve the derived-versus-mutating return distinction and key behavior. |
| `src/Arr.php` `_map()` | The existing map helper returns a new `Arr` with keys retained. | Keep the existing primitive boundary; wrapping its internal native traversal again would add no behavior. |
| `src/Arr.php` `_sort()` / `_sortDesc()` | `_sort()` mutates `_data` with native `sort()`; `_sortDesc()` explicitly emits `Event::CHANGE` for an order-only change before `alter()`. | This is a concrete observer-consistency candidate, not a reason to equate their different key/order contracts. |
| `src/Obj.php` constructor and `field()` | Composes `Arr`, typed field primitives, and behavioral events; a field setter returns the object while a getter unwraps unless value-object exposure is requested. | Preserve field return and event semantics rather than mechanically substituting raw arrays or new wrappers. |

On current `master`, a no-service PHP 8.2.11 fixture starting with
`Arr::make([3, 1, 2])` observes **zero** `Event::CHANGE` callbacks after
`sort()` produces `[1, 2, 3]`; the corresponding `sortDesc()` produces a
different order and emits **one**. A bounded follow-up could make an actual
`sort()` order change observable. Benefit: consistent behavior for callers
listening to primitive mutation. Risks: new callback side effects, sorting
performance, no-op events, comparison/tie behavior, and static versus instance
return shape. Focused tests should cover changed and unchanged order,
callbacks invoked exactly once, keys, comparator failures, and existing
`ArrTest` behavior before any code change. Affected-owner review is needed;
this audit does **not** change the event contract.

`Obj::__sleep()` and `__wakeup()` are a separate serialization audit concern
under #342, not an automatic `Obj` rewrite here. The fresh-object failure has
a test-only draft fixture in that issue's review path.

## Application dispatch boundary

| Evidence | Current convention | Assessment |
| --- | --- | --- |
| `src/Services/Application.php` `process()` / `run()` | `Arr` and `Str` normalize request arguments and routing; behavioral events mark lifecycle decisions. `run()` remains fluent. | Keep package helpers for carried request values and preserve fluent return and event order. |
| `run()` and private `message()` | Native `call_user_func_array()` invokes a selected recipient at the dynamic callable boundary. | Native invocation is justified; wrapping it in a primitive would hide call and exception semantics. |
| `gatewayOutcome()` and console service dispatch | Gateway halts are observable; console handler returns are currently discarded by `run()`. | The additive public result question belongs to #332, not to a mechanical usage refactor. |

For any proposed dispatch correction, benefit and risks must be assessed in
the owning issue: result observability versus duplicate invocation, exception
propagation, stale state on reuse, HTTP output, and callback/event compatibility.
Focused `ApplicationTest` fixtures should distinguish these branches before a
public signature changes. This audit adds no hook, result object, or policy.

## Review boundary

- This inventory proposes no new signatures, dependency, or behavioral edit.
- Preserve existing public names, status conventions, and low-level native
  operations where they expose file or SQLite resource semantics.
- Review #299, #303, and #300 independently before adopting their contracts;
  review #332 and #342 independently for dispatch and serialization. The
  `Arr::sort()` event candidate needs observer compatibility review. Any
  public-facing standards or behavior change needs its own evidence and human
  review before release.
