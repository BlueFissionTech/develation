# Internal usage audit: file and SQLite boundaries

This is the first, evidence-based slice of issue #302. It records current
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

## Review boundary

- This inventory proposes no new signatures, dependency, or behavioral edit.
- Preserve existing public names, status conventions, and low-level native
  operations where they expose file or SQLite resource semantics.
- Review #299, #303, and #300 independently before adopting their contracts;
  any public-facing standards or behavior change needs its own evidence and
  human review before release.
