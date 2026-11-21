# Immutable date inputs and local calendar days (review proposal)

This is a review proposal for issue #322, not a shipped Date API. A local
calendar day is not a fixed 86,400-second duration; the explicit UTC
elapsed-seconds proposal in #272 addresses a different operation.

## Current source boundaries

- `Date::setValue()` accepts a mutable `DateTime` directly or passes other
  values to `new DateTime(...)`. A `DateTimeImmutable` input is not accepted
  through that path. The constructor, `val()` setter, `snapshot()`/`reset()`,
  and `ref()` all carry value state, so adapting only the constructor would
  leave inconsistent behavior.
- `Date` retains the input in `_value` and exposes a mutable internal
  `datetime` object. Directly retaining a caller-owned `DateTime` permits
  later caller mutation to affect a Date. Independent copies and snapshot
  behavior need explicit tests.
- `_timezone` is recorded but not consistently applied to parsing or
  timestamp calculations; #298 owns explicit timezone construction and
  conversion. This proposal must not silently change those existing paths.
- `Val`'s DATETIME validation currently recognizes `DateTime` or a parseable
  value, not `DateTimeInterface` explicitly. The setter path must be checked
  with the constructor path before implementation.

## Proposed additive input and operation contract

Accept any `DateTimeInterface` input in construction and `val(...)` setting
by taking an independent copy that preserves its instant and named timezone.
Retain support for existing `DateTime`, string, timestamp, and empty/default
inputs; do not reinterpret numeric values or process-default timezone here.
Unsupported object inputs should raise a documented `InvalidArgumentException`
instead of an incidental native `TypeError`. A source `DateTime` or
`DateTimeImmutable` must remain unchanged after Date construction, setting,
snapshot/reset, and calendar adjustment.

The proposed instance operation is `addCalendarDays(int $days): Date`. It is
**fluent and mutates only the receiving Date**, returning that same instance.
Positive and negative whole-day counts are accepted; zero is an identity.
The operation should calculate on an independent `DateTimeImmutable` copy in
the Date's named IANA timezone using a calendar-day interval, then update
the Date through its normal change/event path. It must preserve local
wall-clock intent and zone identity rather than adding `days * 86400` seconds
or converting to UTC. No scheduling cutoff or recurrence rule is implied.

The exact gap/fold policy needs review. PHP 8.2 native `DateTimeImmutable::add`
normalizes a nonexistent spring local time forward and chooses an offset for
ambiguous fall time. A package helper can either document that native policy
or reject ambiguous/nonexistent target times; it must not leave the choice
implicit. Invalid timezone names should fail rather than fall back silently.

## Provider-free test matrix for implementation

| Input in `America/New_York` | `+1` calendar day | Elapsed seconds | Concern |
| --- | --- | ---: | --- |
| `2024-03-09 12:00:00-05:00` | `2024-03-10 12:00:00-04:00` | 82,800 | Spring 23-hour day |
| `2024-11-02 12:00:00-04:00` | `2024-11-03 12:00:00-05:00` | 90,000 | Fall 25-hour day |
| `2024-02-28 12:00:00-05:00` | `2024-02-29 12:00:00-05:00` | 86,400 | Leap day |
| `2024-12-31 12:00:00-05:00` | `2025-01-01 12:00:00-05:00` | 86,400 | Year boundary |

The first two rows were checked against native PHP 8.2 calendar-day addition;
they are not evidence that the current `Date` class already implements it.
Also test negative and zero quantities, month boundaries, source-object
identity and timezone unchanged, two independent Date copies from one source,
`val()` setting, snapshot/reset, change-event count, and invalid inputs.
Include spring-gap and fall-fold target times once the ambiguity policy is
reviewed. Tests must not depend on the process-default timezone.

Maintainer and affected-owner review is needed for the signature, mutating
versus derived-return semantics, gap/fold policy, event behavior, and #298
compatibility before code or landing. This proposal does not complete #322.
