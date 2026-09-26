# Async and transport compatibility

DevElation supports PHP 8.2+. This page describes the current source-level
execution boundaries, not a promise that every optional runtime or transport is
available in every installation. The core package does not install or start a
worker, server, provider, or extension.

| Surface | Execution and lifecycle | Prerequisites and unavailable behavior |
| --- | --- | --- |
| `Promise` | `try()` invokes the action in the current PHP call; `then()` callbacks run when the action resolves or rejects. Construction alone does not execute it. | Core PHP; no parallel execution by itself. Existing unit tests cover resolve and reject callbacks. |
| `Async::exec()` / `run()` | `exec()` queues a promise in the default `SplPriorityQueue`; `run()` drains queued callables in the caller's PHP process, one after another. The `max_concurrency` configuration is not a worker-pool guarantee. A provider-free regression test covers explicit drain. | Core SPL. No background worker or concurrent execution is started by `exec()` alone. Destructor drain is an implementation detail, not a scheduling contract; call `run()` explicitly when completion matters. |
| `Fork::do()` | Queues an action that calls `pcntl_fork()` when drained. Parent performs a non-blocking child status check; the caller must separately manage child completion. | Optional `pcntl` function. `do()` throws if unavailable. The process fixture is skipped without it; do not assume this path on Windows or every SAPI. |
| `Thread::do()` | Queues an action that creates a `parallel\Runtime` and waits on the future's `value()` during drain. This is not a general multi-worker scheduler. | Optional `parallel` runtime and a compatible thread-safe PHP build/SAPI. No provider-free lifecycle fixture currently verifies this path; treat it as unverified until tested on the target runtime. |
| `Shell::do()` | Queues a generator action intended to poll `System\Process`, but `Promise::try()` does not iterate the generator returned by that action. The current `Shell::do()` path therefore does not establish that a process starts when the queue drains. | Local `proc_open` capability would be needed for a working process path. The existing shell test accepts a null result and is not execution proof; bounded stderr, timeout, cancellation, and cleanup remain separate contract work. |
| `Remote::do()` | Queues a `Connections\Curl` HTTP request. The current tests require an opt-in network target and are skipped by default. | Optional `curl` extension and reachable target. No offline fixture currently proves retry, failure, or cancellation semantics; do not infer those from method names. |
| `Sock` | Starts a Ratchet WebSocket server and blocks in its run loop until stopped. It is a transport, not an `Async` queue worker. | Optional Ratchet classes. `isAvailable()` / `missingDependencies()` can inspect availability without starting a server; `start()` throws when transport classes are missing. Server lifecycle is not exercised in the default suite. |

Queue storage is separate from execution. `SplQueue` and `SplPriorityQueue` are
process-local; `FileQueue` and `DiskQueue` use local temporary filesystem state;
`DBQueue` uses a configured storage/backend; `MemQueue` and `RedisQueue` need
their respective extension and service. `MemQueue` and `RedisQueue` expose
receipt-based claim, acknowledgement, release, and recovery APIs, but a queue
backend does not itself run jobs. `Async::setQueue()` must not be assumed to
accept every `IQueue` implementation interchangeably: their static enqueue
signatures and payload shapes need separate compatibility proof.

For a reproducible offline baseline, run:

```text
vendor/bin/phpunit --do-not-cache-result tests/Async/PromiseTest.php tests/Async/AsyncQueueTest.php tests/Async/SockTest.php
```

Optional `pcntl`, `parallel`, network, WebSocket, database, Redis, and Memcached
paths require their own target-runtime fixtures and opt-in service checks. Host
authorization, deployment, provider adapters, workflow sequencing, and retry
policy do not belong to this compatibility statement.
