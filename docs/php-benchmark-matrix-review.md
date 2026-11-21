# PHP runtime benchmark matrix (review proposal)

This scopes issue #267. It is a measurement plan, not a performance result,
extension recommendation, runtime support policy, or environment-tuning
instruction. Correctness must pass before timings can be interpreted.

## Package and source inventory

- `composer.json` requires PHP `>=8.2` and the PSR HTTP message/client and
  HTTP discovery packages. It does not declare a required `ext-ds`, OPcache,
  or JIT dependency. The development test runner is PHPUnit 11.5.
- The manifest suggests optional MongoDB, Redis, and Memcached packages or
  extensions. `tests.md` keeps network, MySQL, MongoDB, Redis, and Memcached
  live tests opt-in. `SQLiteLink` checks for `SQLite3`; Redis and other
  integration classes cross their own extension/client boundaries. None of
  these service integrations should be silently added to a primitive
  benchmark baseline.
- A targeted search of `src/` found no `Ds` class or extension reference.
  Presence of `ds` in one developer CLI does not establish that DevElation
  uses it or that installing it accelerates any path. Confirm this inventory
  against the exact measured source before drawing conclusions.
- Current CI selects PHP 8.2 and 8.3 by minor. It does not run a controlled
  OPcache/JIT or `Ds` comparison. The general PHP 8.2–8.5 compatibility
  review is tracked separately in #301; fixed-patch provenance in #333.

On source `23cdf9db8149696d4791614db96d410a3d97f149`, local PHP 8.2.11
CLI passed `tests/ArrTest.php`, `tests/StrTest.php`, and `tests/NumTest.php`:
224 tests, 1,907 assertions. This is a correctness baseline, **not** a
timing, OPcache/JIT comparison, or fixed-patch qualification.

## Proposed bounded matrix

| Axis | Cells to qualify | Required record |
| --- | --- | --- |
| Runtime | Supported PHP 8.2 and 8.3 builds available with approved provenance; later minors only after support review | Exact patch, executable/package hash, CLI or other SAPI, architecture, thread mode, extension list and versions |
| Execution | OPcache off; OPcache on with JIT off; JIT on only where supported and approved | Effective settings measured in the process, not merely requested flags; warm/cold distinction |
| Optional `Ds` | Absent versus present only if an actual package-owned code path uses it | Source path, fallback behavior, identical inputs and correctness; otherwise mark `not applicable` |
| Workload | Representative Val/Arr/Str/Num/Obj/Func operations at small and bounded larger input sizes | Fixture generator/seed, item/byte counts, result checksum, iteration count and elapsed/memory method |

Run correctness tests for the exact source and runtime first. Use fresh,
isolated CLI processes with the same dependencies and fixtures; warm up each
cell consistently; record repeated wall-time and peak-memory observations
with dispersion rather than one best run. Keep order effects, cache state,
extensions and runtime build explicit. A timing difference without equivalent
outputs or parity of execution context is not a speed result. No source
branch should silently choose a faster but behaviorally different algorithm.

## Review and execution gate

Maintainers choose representative fixtures, sample size, measurement budget,
and whether an OPcache/JIT comparison is worth running. Do not install
extensions, alter `.env`, change machine or production PHP settings, enable
services, or infer a public optimization recommendation from this plan.
Report unavailable matrix cells as `unknown` or `not applicable`, never as
passing. Review actual measurements and correctness before documenting any
performance guidance or changing release policy.
