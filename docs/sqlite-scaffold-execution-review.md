# SQLite scaffold execution and idempotence (review proposal)

This proposal scopes issue #300. It records current behavior and testable
decisions; it is not an adopted schema API, migration plan, or promise that
SQLite can perform every requested DDL operation in place.

## Current source and tests

- `SQLiteScaffold::create()` and `delete()` accept an optional already-open
  `SQLiteLink`. The supplied-link path runs the query, publishes an event with
  operation/entity/status metadata, returns no value, and does not print.
- Omitting the link takes a different path through `Data\Storage\SQLite` and
  prints a status line. Preserve this legacy behavior until a migration path
  is reviewed; it is not an explicit execution context.
- `alter()` is currently a no-op and accepts no connection. `SQLiteStructure`
  builds a `CREATE TABLE` statement, not an alter or schema-diff plan. Index
  operations are not represented by the scaffold's public methods.
- Existing scaffold tests prove borrowed create/delete target selection,
  duplicate-create failure status, caller-owned rollback, silence on the
  borrowed path, and continued use of the owner-held connection. They do not
  establish idempotent create/alter/index or invalid-identifier behavior.

## Decisions for maintainer and affected-owner review

1. **Execution context.** Specify an additive explicit path requiring a
   supplied, already-open link. Define what happens for missing, closed, and
   cross-database links. It must not change a global default database, close
   the borrowed connection, or commit/rollback a caller transaction.
2. **Outcome.** Define a quiet, operation-scoped success/failure result or
   other inspectable failure contract. Coordinate representation with #290 so
   callers do not have to infer success from `void`, stdout, or a link status
   overwritten by a later query. Preserve `Event::CREATED`/`DELETED` and
   failure observers or document a reviewed additive migration.
3. **Idempotence.** Define what counts as equivalent schema before skipping a
   repeated operation. A table or index with the same *name* but incompatible
   columns, types, constraints, or uniqueness must not be silently accepted.
   Decide whether comparison uses declared schema, SQLite affinity, and/or
   normalized metadata; do not infer equality from existence alone.
4. **Supported DDL.** Choose the smallest supported alter/index operations
   under SQLite's actual limitations. Unsupported or unsafe mutations must
   fail visibly rather than be treated as success. Automatic table rebuilds,
   data copying, destructive migrations, and legacy schema rewrites need
   separate explicit approval and recovery design.
5. **Identifiers and transactions.** Define identifier validation/quoting
   before interpolation. Test caller `BEGIN`/`ROLLBACK` for create, supported
   alter/index, and failure; do not promise nested savepoints or generic
   concurrency policy here (#274).

## Fixture matrix after contract selection

Using temporary databases on PHP 8.2+, test two links to distinct files;
repeated equivalent and mismatched create; supported alter and index applied
twice; invalid identifiers; absent/closed links; failed operation followed by
link reuse; and caller rollback of both successful and failed DDL. Assert the
chosen outcome belongs to the operation, no explicit-path stdout appears,
the owner-held connection remains usable, and no writes reach the other file.
Keep result-cursor ownership in #290 and binary-storage migration in #335.

No implementation, dependency, service, release, or downstream migration is
authorized by this review proposal. Resolve the above contract and
compatibility questions before promoting production behavior.
