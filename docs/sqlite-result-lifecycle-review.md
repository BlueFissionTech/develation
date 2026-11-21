# SQLite execution outcomes and result lifetime (review proposal)

This is a contract proposal for issue #290, not an adopted API or a claim of a
reproduced resource leak. Review the choices below before changing public
methods. Keep transaction/savepoint policy and schema-diff behavior separate.

## Current source boundary

- `SQLiteScaffold::create()` and `delete()` return no outcome. With a supplied
  `SQLiteLink`, they call `query()`, leave the connection with its owner, and
  publish success/failure events using the link's current status. With no link,
  they use the historical implicit SQLite storage path and print a status line.
- `SQLiteLink::query()` places a raw `SQLite3Result` or `false` in the inherited
  result slot and records a status string. A subsequent query overwrites that
  slot. `Connection::result()` returns the slot; `Connection::close()` does not
  explicitly dispose it.
- The bound-query path closes its prepared statement on failed execution or
  an exception. On success it returns the result without an explicit disposal
  operation in this method. This source observation does **not** establish
  whether a particular PHP/SQLite build leaks a native handle.
- Existing scaffold tests prove borrowed create/delete success, duplicate
  create failure status, host rollback, no cross-database write, and reuse of
  an owner-held connection. Existing link tests cover typed binding, missing
  bindings, failed raw queries, and peer-link reuse. They do not prove early
  cursor disposal or a stable per-operation scaffold outcome.

## Decisions required before implementation

1. **Scaffold outcome.** Choose an additive inspectable result or an explicit
   supported failure signal for the supplied-link path. A caller must be able
   to distinguish success from failure without parsing stdout or relying on a
   later, potentially overwritten link status. Specify how operation, entity,
   status, and error details are represented. Decide separately how the
   historical omitted-link path remains compatible or migrates. Do not infer
   that `void` means success.
2. **Cursor ownership.** State whether the caller or `SQLiteLink` owns a result
   returned from `result()`. Specify what happens when a new query replaces the
   slot, when a caller abandons a cursor early, when a bound query fails, and
   when the link closes. Do not invalidate a separately owner-held shared
   connection merely to dispose a cursor.
3. **Observation after failure.** Specify whether the prior result remains
   readable or is invalidated after a failed query, and how a caller obtains
   the failure for the *same operation* after a later successful query. Preserve
   exact SQLite errors where available without promising their text is stable.
4. **Compatibility.** Review existing `query()->status()`, `result()->fetchArray()`
   and scaffold event users before altering result disposal or return shape.
   Changes to prepared-statement lifetime need PHP 8.2+ SQLite3 validation.

## Acceptance fixtures for the agreed contract

Use temporary SQLite databases and no external service. Test successful and
failed borrowed scaffold operations, then a later query on the same link;
repeated bound queries; a partially read result followed by replacement;
explicit early cursor release if the chosen API exposes it; link close and
reopen; and two links sharing one underlying connection. Assert the chosen
outcome is tied to its operation, that no owner transaction is committed or
rolled back, and that the peer link remains usable. Add a bounded native-handle
check only when its measurement and platform behavior are defined; do not label
an unmeasured case a leak or claim streaming support from these fixtures.

## Review gate

Maintainer and affected-owner review must settle the outcome representation,
cursor ownership, compatibility path, and independently executable fixtures
before production changes or landing. No dependency, migration, release, or
consumer-specific adapter is implied by this proposal.
