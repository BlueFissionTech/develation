# Runtime and extension benchmark matrix

DevElation requires PHP 8.2 or newer. Its Composer runtime requirements name
PHP and three PSR/HTTP packages, but no `ext-*` package requirement. That does
not mean every optional adapter works in a minimal installation. Benchmark
claims must identify the exact runtime and optional capabilities used.

## Capability inventory

| Capability | Source boundary | Prerequisite | Baseline status |
| --- | --- | --- | --- |
| Primitives and provider-free tests | `src/Val.php`, `src/Str.php`, `src/Arr.php`, `src/Num.php` | PHP 8.2+ and Composer runtime packages | Package baseline |
| HTTP curl transport | `src/Connections/Curl.php` | `ext-curl` | Optional |
| SQLite and MySQL links | `src/Connections/Database/SQLiteLink.php`, `MySQLLink.php` | `ext-sqlite3`, `ext-mysqli`, and a configured database where applicable | Optional |
| Redis and Memcached storage/queues | `src/Connections/Redis.php`, `src/Data/Queues/RedisQueue.php`, `MemQueue.php` | Matching extension and service | Optional |
| MongoDB link | `src/Connections/Database/MongoLink.php` | `mongodb/mongodb`, `ext-mongodb`, and a configured service | Optional |
| Fork and thread paths | `src/Async/Fork.php`, `Thread.php` | `pcntl` or `parallel` respectively, on a supported PHP build/platform | Optional |
| WebSocket server | `src/Async/Sock.php` | Compatible Ratchet packages | Optional |
| `Ds` acceleration | No `Ds` class use or Composer suggestion in this source tree | None | Not a current optimization path |

This inventory is based on `composer.json` and source references; it is not a
promise that optional integrations were exercised. See [tests.md](tests.md)
for opt-in integration setup and [async.md](async.md) for execution semantics.

## Reproducible matrix

Run the provider-free correctness suite before timing each profile. Record the
PHP version, operating system, SAPI, extension versions, `opcache.enable_cli`,
`opcache.jit`, and `opcache.jit_buffer_size` alongside the result. Keep the
same checkout, Composer dependency versions, fixture sizes, iteration count,
and machine load when comparing profiles. Do not compare CLI measurements with
a web SAPI as if they were the same environment.

| PHP runtime | Profile | Correctness gate | Timing fixture |
| --- | --- | --- | --- |
| 8.2 | CLI without OPcache | Provider-free PHPUnit suite passes | `examples/benchmark-primitives.php` at 32, 256, and 1024 elements |
| 8.2 | CLI OPcache enabled, JIT disabled | Same suite passes | Same fixture and iterations |
| 8.2 | CLI OPcache and JIT enabled where available | Same suite passes; record actual JIT status | Same fixture and iterations |
| 8.3 and later supported versions | Repeat available profiles | Same suite passes on each runtime | Same fixture and iterations |

Example CLI profile commands (substitute the intended PHP executable and run
each command from the repository root):

```text
php -d opcache.enable_cli=0 vendor/bin/phpunit --do-not-cache-result
php -d opcache.enable_cli=0 examples/benchmark-primitives.php 256 500
php -d opcache.enable_cli=1 -d opcache.jit=0 vendor/bin/phpunit --do-not-cache-result
php -d opcache.enable_cli=1 -d opcache.jit=0 examples/benchmark-primitives.php 256 500
php -d opcache.enable_cli=1 -d opcache.jit_buffer_size=64M -d opcache.jit=tracing vendor/bin/phpunit --do-not-cache-result
php -d opcache.enable_cli=1 -d opcache.jit_buffer_size=64M -d opcache.jit=tracing examples/benchmark-primitives.php 256 500
```

The fixture reports a median of five in-process samples after warmup; it
checks its expected result before each timed case. It is a comparison aid,
not a production workload or a native-PHP speed contest. If OPcache/JIT is
unavailable, record a skipped profile instead of treating requested ini flags
as proof that acceleration ran. Preserve raw results and variance, repeat on
more than one run, and require maintainer review before making a public
performance recommendation. Environment tuning, extension installation,
deployment, and release policy are outside this matrix.
