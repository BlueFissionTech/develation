## Purpose

This branch starts the Composer and Packagist readiness pass while fixing the `Arr::count($array)` static helper contract. The goal is to keep the first release-hygiene PR narrow: make the package metadata publishable, make CI failures visible, and cover the static count behavior that internal helpers already expect.

## Scope

- allow `Arr::count($array)` through the DevElation static helper path
- keep `$arr->count()` available through the existing dynamic helper dispatch
- update package metadata so `composer validate --strict` accepts the package shape
- update public installation docs for Composer and the canonical GitHub source
- add release archive excludes for development-only files
- remove CI's PHPUnit `continue-on-error` setting

## Out of Scope

- broad datatype refactors outside `Arr`
- changing optional service test behavior
- running dependency installs or updates without explicit approval
- changing Packagist account or webhook settings from this branch

## Acceptance Criteria

- `Arr::count(['a', 'b'])` returns `2`
- `$arr->count()` still returns the instance array count
- Composer metadata has no publish-blocking schema errors
- Composer install instructions point at `bluefission/develation`
- CI treats PHPUnit failures as failures
- package archives omit tests, CI config, local harness files, and other development-only artifacts
