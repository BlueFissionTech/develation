# Async queue adapter contract: review candidate

This is the source-backed first slice of #276, not a declaration that every
`IQueue` can execute `Async` tasks. `async.md` describes execution and optional
transport limits. No adapter, public signature, or runtime behavior changes in
this document.

## Current call shape

`Async::exec()` constructs a `Promise` and `Async::keep()` wraps it in a
callable generator. `keep()` passes the following positional arguments to its
configured queue:

```php
$queueClass::enqueue(
    ['data' => $wrappedCallable, 'priority' => $priority],
    'async_queue'
);
```

`Async::run()` calls `isEmpty('async_queue')`, then
`dequeue('async_queue')`, invokes the returned value as a callable, and
iterates its generator synchronously in the calling PHP process. The default
`Data\Queues\SplPriorityQueue` accepts the *item first, queue second* call,
extracts the `data` field, and is process-local. The queue class is cached
when the singleton is first constructed; `setQueue()` after that does not
update the cached task queue.

| Queue surface | `enqueue` position | `dequeue` shape | Async status |
| --- | --- | --- | --- |
| `SplPriorityQueue` | item, queue | wrapped callable | Current default; provider-free explicit-drain tests in `tests/Async/AsyncQueueTest.php`. |
| `SplQueue` | queue, item | stored item | Not compatible with `keep()` positional order. A PHP 8.2.11 `setQueue(SplQueue::class)` then `exec()` probe raised `TypeError: Illegal offset type in isset or empty` before drain. |
| `Queue`, `FileQueue`, `DiskQueue`, `DBQueue`, `MemQueue`, `RedisQueue` | queue, item | backend-specific payload | Not qualified as `Async` task adapters. Persistence or transport does not make closures serializable, executable by another worker, retryable, or owned by a scheduler. |

`IQueue` documents queue-name-first `enqueue($queue, $item)`, but PHP method
compatibility cannot validate the semantic order of two untyped parameters.
The default `SplPriorityQueue` reverses that order while implementing
`IQueue`. Therefore `IQueue` membership alone is insufficient evidence for
`Async::setQueue()`.

## Proposed compatibility decision

An `Async` task adapter would need an explicit, testable contract for:

- task enqueue with priority and queue name without ambiguous positional
  reordering;
- `isEmpty()` as a boolean for the same queue, and `dequeue()` returning one
  callable wrapper or an explicit empty outcome;
- stable ordering/tie behavior, error propagation, and what remains queued
  when a task fails;
- adapter selection before the first task, or a documented safe reset/migration
  operation for an already constructed singleton.

Review whether to introduce a dedicated adapter interface or an explicit
normalizing adapter around queue-name-first in-memory queues. Do not silently
reinterpret existing `IQueue` methods or claim persisted closure support.
Before changing `setQueue()`, cover default compatibility, a supported
in-memory alternative, an incompatible class, late reconfiguration, priority
and ties, empty dequeue, and failure recovery with provider-free tests. A
persisted backend would additionally need a separately reviewed task
serialization, worker ownership, acknowledgement, and retry contract.

No host, service, dependency, or release change is authorized by this review
candidate.
