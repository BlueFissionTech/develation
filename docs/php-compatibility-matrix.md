# PHP 8.2–8.5 compatibility: first evidence matrix

This is the first bounded review slice for issue #301. It records source and test
evidence, not a support-floor change or a claim that untested runtimes work.

| Runtime | Declared / observed state | Evidence gap |
| --- | --- | --- |
| PHP 8.2 | `composer.json` declares `>=8.2`; the CI matrix includes 8.2. Focused local tests on 8.2.11 are recorded below. | Security support ends **2026-12-31**. A promise to retain 8.2 for another 12 months needs a separately approved EOL exception or revised support policy. |
| PHP 8.3 | CI matrix includes 8.3; the current base has passing CI. | A green suite is not a complete deprecation or optional-extension inventory. |
| PHP 8.4 | Not in CI and not installed for this slice. | Run parse and behavior checks, especially implicitly nullable typed parameters, before claiming support. |
| PHP 8.5 | Not in CI and not installed for this slice. | Run deprecation and behavior checks, especially cURL disposal and serialization, before claiming support. |

The dates above come from [PHP's supported-version schedule](https://www.php.net/supported-versions.php/).
The 8.2 floor remains unchanged until a reviewed support decision; neither
retaining it past upstream security support nor raising it is approved here.

## Package and optional-service boundary

`composer.json` requires PHP 8.2+, PSR HTTP Message/Client interfaces, and
HTTP discovery. Ratchet is a development dependency and optional websocket
transport. MongoDB, Redis, and Memcached integrations are suggestions with
their respective optional packages/extensions. `tests.md` makes live service
tests opt-in. This matrix does not certify those integrations on every runtime.
The sole `.github/workflows/ci.yaml` PHP matrix currently runs 8.2 and 8.3;
adding 8.4/8.5 entails separate CI cost and extension-availability review.

## Representative source risks, not an exhaustive scan

| Version | Official migration rule | Current source evidence | Next proof |
| --- | --- | --- | --- |
| 8.3 | [Deprecations](https://www.php.net/manual/en/migration83.deprecated.php) include several core/extension cases. | No `SQLite3::enableExceptions(false)`, no zero-argument `get_class()`/`get_parent_class()`, and no `assert_options()` call found in `src/` by a targeted search. | Run the suite with deprecations visible; inspect any observed notices before changing code. Absence from a search is not full compatibility proof. |
| 8.4 | [Implicitly nullable typed parameters are deprecated](https://www.php.net/manual/en/migration84.deprecated.php). | Examples include `Dispatches::__construct(HandlerCollection $handlers = null)`, `Promise::then(callable $onReject = null)`, and `Arr::_slice(int $length = null)`. | Verify on 8.4, then review explicit nullable signatures for inheritance and named-argument compatibility. |
| 8.5 | [Deprecations](https://www.php.net/manual/en/migration85.deprecated.php) include `curl_close()` and soft-deprecation of `__sleep()`/`__wakeup()`. | `Connections/Curl::_close()` calls `curl_close()`. `Obj` declares both magic methods; native trusted serialization currently fails on a fresh `Obj`, characterized separately in #342 / draft PR #370. Several tests call reflection `setAccessible()`, also listed as deprecated in 8.5. | Run a real 8.5 suite before claiming outcomes. Keep cURL handle-release and trusted serialization behavior separate from untrusted deserialization policy. |

No 8.4 or 8.5 execution occurred for this slice, and no newer syntax or helper
is proposed while the PHP 8.2 floor remains declared.

## Focused no-network probe

`tests/Connections/CurlHandleLifecycleTest.php` opens a cURL handle for a
synthetic URL with host validation disabled, makes **no request**, closes it,
checks that the public connection handle is released, and captures any
deprecation during that lifecycle. It is a local parity probe, not network or
PHP 8.5 conformance evidence. It skips only when the cURL extension is absent.

On local PHP 8.2.11, `tests/ObjTest.php` passes 17 tests / 74 assertions;
network-gated `tests/Connections/CurlTest.php` skips both tests. The separate
#342 fixture reproduces fresh-`Obj` serialization failure locally on 8.2.11
and in 8.3 CI; it must not be mistaken for a fixed serialization contract.

Before expanding CI or changing runtime support, review maintainer capacity,
extension parity, real 8.4/8.5 results, affected-consumer contracts, and the
8.2 end-of-security-support decision. No dependency, API, release, or support
policy change is made by this matrix.
