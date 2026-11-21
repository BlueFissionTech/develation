# Reliable Redis publication and retirement (review proposal)

This is a review proposal for #339, not an adopted API, production retention
policy, or claim of real-Redis concurrency proof. Keep the existing queue
engine and `IReliableQueue` contract intact while the opt-in extension is
designed.

## Current source boundary

- `RedisQueue::enqueue($queue, $item)` generates a new random delivery ID on
  every call. Its Lua script writes payload/attempt metadata and pushes that
  ID to the ready list. There is no caller-supplied identity, suppression
  index, or available-at admission in this path.
- `claim()` consumes ready IDs, issues a claim token, increments attempts, and
  stores a lease expiry. `acknowledge()` and `release()` check the token;
  `recover()` handles expired leases. `QueueReceipt` carries ID, payload,
  attempts, lease expiry, and token. Preserve these observable semantics.
- `purge()` deletes queue keys and cannot be treated as selective retirement
  of completed suppression metadata. Offline fake-client tests cover basic
  FIFO/FILO, token rejection, release, recovery, and retry exhaustion. The
  opt-in real-Redis integration test covers a claim/release/ack cycle, not
  duplicate publication or retirement races.

## Decisions for maintainer and affected-owner review

1. **Opt-in publication shape.** Choose a general signature for caller-owned
   stable delivery identity, idempotency key, and availability time. Specify
   return behavior when the same key is retried with the same versus a
   different payload/identity. Preserve ordinary `enqueue()` behavior when
   no new option is supplied; do not reshape `QueueReceipt` without review.
2. **Atomic admission.** Define the Redis keys and single atomic transition
   that records a suppression key and either enqueues a ready item or delays
   it until due. A worker must not claim early. Specify how due items move to
   ready without an implicit service, timer, or unbounded scan.
3. **Clock and validation.** Define time units, UTC/epoch interpretation,
   injectable test clock, and bounds for availability, lease, attempts, and
   retirement counters. Reject invalid values *before* writes. No default
   production suppression period or auto-scheduled cleanup is selected here.
4. **Completion and retirement.** Decide which terminal state makes
   suppression metadata eligible for queue-scoped, indexed, bounded deletion.
   Never retire ready, delayed, leased/recoverable, failed, outbox, legacy, or
   inconsistent records. A concurrent retry/publish/ack/retire race must not
   create a second live delivery or delete another claimant's token.
5. **Ownership and migration.** Redis admission can suppress duplicate queue
   publication, not replace durable authoritative mutation idempotency in a
   caller database. Define compatibility with existing FIFO/FILO, retry,
   failed-item, `IReliableQueue`, and legacy keys. Keep payloads out of metrics
   and diagnostics; do not make the public API specific to one job envelope.

## Staged fixture plan

First add offline contract tests for validated opt-in inputs, same-key retry,
conflicting-key policy, future availability, legacy `enqueue()`, and unchanged
receipt/token behavior. Separately use an isolated real Redis service to test
two producers racing to publish one stable key; due-time admission; wrong or
stale token acknowledgement; release/recovery; and bounded retirement racing
with publish/ack. Assert exact ready/delayed/leased/terminal counts and IDs,
not merely a truthy return. Report fake-client proof and real concurrency
proof separately. Do not enable a service, install dependencies, purge any
shared queue, or run production cleanup from this proposal.

Review the contract, compatibility, clock policy, retention authority, and
test capacity before implementation or landing. Publication/admission and
retirement should be separate bounded behavior slices if approved.
