## Purpose

Issue `#97` is a narrow maintenance sweep for Develation's `Num` primitive. The goal is to improve confidence in existing numeric helper behavior without expanding the primitive surface or changing stable contracts.

## Scope

- review `src/Num.php` against `tests/NumTest.php`
- add focused coverage for existing arithmetic, conversion, and formatting helpers
- apply only low-risk fixes if a concrete defect is exposed by the new tests

## Out of Scope

- adding new numeric primitives
- broad refactors of datatype architecture
- dependency, environment, or CI changes

## Acceptance Criteria

- the branch is linked to issue `#97`
- `Num` helper behavior is covered more directly than inherited `Val` behavior alone
- any implementation change is justified by a failing test
- targeted `Num` tests pass after the sweep
