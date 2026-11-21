# SQLiteLink transaction and conditional-update contract (review proposal)

This scopes issue #274. It is not an adopted transaction API, concurrency
guarantee, database migration, or queue/reservation service. Keep result-handle
ownership (#290), scaffold schema execution (#300), and noncreating link mode
(#303) separate.

## Existing surface to preserve

- `SQLiteLink` exposes connection open/close, `query()` (including bound
  values), `status()`, `result()`, and the underlying `connection()`. It
  internally calls `lastInsertRowID()` in write paths, but does not expose a
  reviewed public transaction, affected-row, or last-insert-ID contract.
- The link can share an underlying SQLite connection with a peer for the same
  database. Existing tests prove distinct database targeting, bound query
  values, failed-query status, peer usability after one link closes, and
  reopening. Borrowed scaffold tests prove a caller-held raw `BEGIN` and
  `ROLLBACK` can surround create/delete without the scaffold committing it.
- These tests do not prove concurrent conditional updates, restart-safe
  reservation, busy-timeout policy, WAL behavior, or nested transactions.
  Do not infer those capabilities from a green single-process suite.

## Decisions for maintainer and affected-owner review

1. **Transaction ownership.** Decide whether an additive link-level
   begin/commit/rollback API owns a transaction or merely reports the state
   of a caller-managed one. Specify repeated begin, commit/rollback without
   begin, close while active, exception/failure cleanup, and multiple links
   sharing one underlying connection. No implicit commit/rollback of a
   caller-owned transaction.
2. **Affected-row and identity observation.** Choose how a caller obtains
   affected-row count and last-insert ID for its *own* completed statement,
   including zero-row conditional updates, failure, and a later query that
   overwrites link status. Preserve the existing `query()->status()` and
   `result()` behavior or publish a reviewed additive migration. Do not
   interpret zero affected rows as database failure by default.
3. **Conditional update.** Define a minimal compare-and-update contract using
   bound values and observable affected-row count. State whether row identity
   is caller supplied or generated, and what can be guaranteed across two
   independent SQLite connections. A database primitive must not encode
   worker leases, queue retry, or application reservation policy.
4. **Runtime configuration.** Define who may request WAL and busy-timeout
   behavior, when those settings apply, and how unsupported/failed settings
   are reported. Do not silently change default journal or lock-wait policy,
   nor promise that a timeout alone prevents lost updates.
5. **Failure and compatibility.** Specify status/error propagation, retry
   boundaries, rollback responsibility and post-failure reuse. Review users
   of raw `connection()`, shared links, bound queries, and scaffold events
   before adding methods or changing connection lifetime.

## Fixture matrix after contract selection

Use two independent links to a temporary SQLite file and an explicit schema
with row identity and version. Exercise begin/commit/rollback, zero-row and
one-row conditional updates, failure followed by rollback and reuse, exact
affected-row/insert-ID observations, a second writer contending for the same
row, and reopening a link after a completed transaction. Record the exact
transaction mode, journal/busy configuration, source/runtime, observed SQL
status, and final row; do not claim a race is proven by a single sequential
test. Add bounded concurrent and restart fixtures before claiming
restart-safe reservation. Avoid services, persistent data, or external
process utilities in baseline tests.

No implementation, deployment tuning, migration, dependency, release, or
consumer-specific adapter is authorized by this proposal. Resolve the API
and concurrency evidence with maintainers and affected owners first.
