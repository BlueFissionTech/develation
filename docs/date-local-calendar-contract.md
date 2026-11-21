# Immutable date inputs and local calendar days

`Date` accepts independent copies of `DateTimeInterface` inputs and provides
`addCalendarDays(int $days)` for a fluent local-calendar adjustment. A local
calendar day is not a fixed 86,400-second duration; the explicit UTC
elapsed-seconds proposal in #272 addresses a different operation.

## Source boundaries addressed and preserved

- Previously `Date::setValue()` retained mutable `DateTime` inputs directly
  and could not construct from `DateTimeImmutable`. Construction and `val()`
  setting now copy either interface implementation before retaining it.
- `Date` still exposes a mutable internal `datetime` object. The copy contract
  protects caller-owned inputs and independent Date instances, not direct
  mutation of that exposed internal object. Snapshot/reset is exercised.
- `_timezone` is recorded but not consistently applied to parsing or
  timestamp calculations; #298 owns explicit timezone construction and
  conversion. This slice does not change those existing paths.
- `Val`'s DATETIME validation now recognizes native `DateTimeInterface` and
  parseable scalar or stringable values. This allows the Date setter to carry
  an independent native copy through Val's validation path.

## Input and operation contract

Any `DateTimeInterface` or `Date` input in construction and `val(...)` setting
is copied independently, preserving its instant and named timezone.
Retain support for existing `DateTime`, string, timestamp, and empty/default
inputs; do not reinterpret numeric values or process-default timezone here.
Unsupported object inputs should raise a documented `InvalidArgumentException`
instead of an incidental native `TypeError`. A source `DateTime` or
`DateTimeImmutable` must remain unchanged after Date construction, setting,
snapshot/reset, and calendar adjustment.

For a Date bound through `ref()`, copying or adding calendar days first reads
the current referenced value. A calendar adjustment writes the formatted date
string back to that external variable, preserving the scalar reference
contract rather than replacing it with a mutable `DateTime` object.

The instance operation is `addCalendarDays(int $days): Date`. It is
**fluent and mutates only the receiving Date**, returning that same instance.
Positive and negative whole-day counts are accepted; zero is an identity.
The operation calculates on an independent `DateTimeImmutable` copy in
the Date's named IANA timezone using a calendar-day interval, then update
the Date through its normal change/event path. It preserves local
wall-clock intent and zone identity rather than adding `days * 86400` seconds
or converting to UTC. No scheduling cutoff or recurrence rule is implied.

The gap/fold policy follows native PHP `DateTimeImmutable::add`: PHP normalizes
a nonexistent spring local time forward and chooses an offset for an ambiguous
fall time. This is an explicit native-parity choice, not a scheduling policy.
Invalid named zones are rejected by `DateTimeZone`; explicit timezone-string
construction remains governed by #298 and is not changed in this slice.

```php
$source = new \DateTimeImmutable('2024-03-09 12:00:00', new \DateTimeZone('America/New_York'));
$date = (new \BlueFission\Date($source))->addCalendarDays(1);
// $date->datetime is 2024-03-10 12:00:00 America/New_York (-04:00).
// $source is unchanged.
```

## Provider-free test matrix for implementation

| Input in `America/New_York` | `+1` calendar day | Elapsed seconds | Concern |
| --- | --- | ---: | --- |
| `2024-03-09 12:00:00-05:00` | `2024-03-10 12:00:00-04:00` | 82,800 | Spring 23-hour day |
| `2024-11-02 12:00:00-04:00` | `2024-11-03 12:00:00-05:00` | 90,000 | Fall 25-hour day |
| `2024-02-28 12:00:00-05:00` | `2024-02-29 12:00:00-05:00` | 86,400 | Leap day |
| `2024-12-31 12:00:00-05:00` | `2025-01-01 12:00:00-05:00` | 86,400 | Year boundary |

The tests exercise the matrix against PHP 8.2 `Date`, plus negative and zero
quantities, source-object and timezone independence, two copies from one
source, `val()` setting, snapshot/reset, change-event count, invalid objects,
and native spring-gap and fall-fold behavior without relying on the process
default timezone.

The maintainer approved implementation on the preceding contract PR. New
implementation still needs exact-head review and #298 compatibility evidence
before landing or issue closure.
