# SQLiteLink open and error contract: review candidate

This is a source-backed design slice of #303, not a new supported API. The
default connection and status behavior remain unchanged. Transaction and
savepoint policy belongs to #274, result-handle disposal to #290, and schema
execution context to #300.

## Current behavior on PHP 8.2

`SQLiteLink::_open()` calls `acquireConnection($database)`, which pools a
`SQLite3` connection by `databaseKey($database)` alone. A new connection uses
`new SQLite3($database)` with its default flags. An isolated PHP 8.2.11 probe
confirmed that opening a missing database path creates the file. A second
missing-table query returned the link without throwing and set status to an
SQLite prepare-error message. The probe closed its link and removed its own
temporary file; it did not touch a shared database.

`query($sql, $bindings)` catches preparation/binding/execution `Throwable`
and stores the message as status. The non-bound path also turns a false query
result into status. `_open()` catches connection exceptions and stores status.
Callers must inspect the current link status; a fluent return is not proof of
success. `tableExists($table, $database)` can also construct a temporary
`SQLite3` connection for a path not already in the pool, so it is not a
noncreating existence probe.

## Decisions for an additive contract

| Boundary | Proposed invariant to review | Compatibility question |
| --- | --- | --- |
| Read-only existing open | A named mode uses SQLite's read-only flag and never creates a missing file. Reject an absent file without substituting a writable pooled connection. | Should it be a constructor config value or a named open method? How is an in-memory database handled? |
| Pool identity | Reuse only connections compatible in path **and** open mode; borrowed links remain valid while owners hold references. | Should read-only and read/write links ever share a connection? What happens when an existing link changes config? |
| Strict query | Opt-in call exposes the original prepare/bind/execute exception or an equally inspectable structured failure while preserving the current fluent, status-based `query()` behavior. | Separate `queryOrThrow()` or explicit strict option? Which error type and event/status ordering? |
| Owner transaction | A callee using a supplied link never closes, commits, rolls back, or replaces the owner-held connection. | How do strict failures interact with an open transaction and subsequent safe reuse? |
| Read-only schema helpers | Failure of a schema query is observable; a missing-file check cannot create it. | Should `tableExists()` gain a noncreating mode, or should callers use a distinct read-only link? |

## Required implementation evidence

Before choosing a public signature, review affected link and scaffold callers.
Then test on PHP 8.2+: absent-file read-only open (file stays absent), existing
read-only query, denied setup, cross-file and cross-mode pool isolation,
shared owner transaction with rollback, failure followed by reuse, and exact
bound null/zero/empty-string values. Exercise prepare, bind, execute, and
schema failures separately. Confirm no stdout leakage and that the existing
non-strict path preserves its status contract. Optional service integration is
not needed for SQLite's local test path.

This document makes no data migration, dependency, service, release, or
consumer-adoption decision.
