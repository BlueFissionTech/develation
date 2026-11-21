# Fixed PHP patch qualification: evidence and gate

This is the first evidence slice for issue #333. It is **not** a security
clearance, fixed-patch compatibility certification, web-host inventory, or
release decision. The general 8.2–8.5 support-policy audit is tracked
separately in #301.

## Exact baseline observed

| Source ref | Runtime | Surface | Result | Classification |
| --- | --- | --- | --- | --- |
| `23cdf9db8149696d4791614db96d410a3d97f149` | PHP 8.2.11, CLI, ZTS; executable SHA-256 `f2d679b234c73cc6dab56a09c2664f5bf806bc5d7d9d5b8fa0332d521eee3e84` | Focused offline Obj, Func, Authenticator, FileSystem, SQLiteLink tests | 117 tests, 707 assertions, passing | Partial old-runtime baseline only |
| Same source | PHP 8.2.35 fixed patch | CLI and extensions | Not run; approved executable provenance and invocation authority not established for this slice | **unknown** |
| Same source | PHP 8.3.36 / 8.4.27 / 8.5.12 fixed patches | CLI and extensions | Not run with exact patch builds | **unknown** |
| Any deployed source | Any patched or vendor-backported PHP | Web SAPI, worker or host | No host/image attestation or service invocation | **unknown** |

The baseline command was:

```text
php vendor/bin/phpunit --do-not-cache-result tests/ObjTest.php tests/FuncTest.php tests/Services/AuthenticatorTest.php tests/Data/FileSystemTest.php tests/Connections/Database/SQLiteLinkTest.php
```

The observed CLI provided `curl`, `dom`, `json`, `mbstring`, `pdo_sqlite`,
`session`, `sqlite3`, and XML modules among others. This is not an exact
extension-parity claim for a patched runtime, optional service, or web SAPI.
The test subset does not prove the full suite, security patch, or deployed
host. CI selecting a PHP *minor* also does not prove a fixed patch level.

## Evidence required before any compatibility claim

1. Identify an **approved existing** fixed-patch executable or attested
   vendor-backported image. Record version, SAPI, package/vendor provenance,
   hash, architecture, thread mode, extension/module versions, and the exact
   DevElation source and lock/platform constraints. Do not install, search
   credentials, or change runtime configuration under this issue.
2. Run the same offline subset with an explicit approved executable (not an
   ambiguous PATH `php`), then lint and run other representative offline
   families with matching required extensions. Record exact commands,
   pass/fail/skip totals, and every unavailable test family. A failed or
   missing extension is not a passing compatibility result.
3. If source failures appear, isolate each minimal reproducer on a separate
   issue branch and review its compatibility. Keep optional MySQL/Mongo/Redis,
   network, and other service tests opt-in; do not activate services solely
   to fill this matrix.
4. Return `compatible-tested`, `incompatible`, `unknown`, or
   `not-applicable` **per exact source/runtime/SAPI pair**. Route host and
   worker attestation to the owning operator; a CLI result cannot qualify a
   web-serving runtime. Coordinate the final evidence with the runtime and
   release owners before any support or promotion claim.

No dependency, configuration, deployment, runtime install, production test,
merge, release, or support-floor change is authorized by this document.
