# UTC duration subtraction

`Date::subtractUtcSeconds()` is an opt-in immutable operation, separate from
the existing mutable `Date` value operations and from scheduling policy.

## Existing surface and constraints

- `Date` wraps a mutable `DateTime`. Its constructor accepts a `DateTime` or
  parseable value, and its default formatted value uses `c`.
- `Date::diff()` reports an absolute numeric difference. It cannot express a
  directional, immutable subtraction result.
- `Date::formatTimestamp()` uses PHP's process-default timezone. It is not an
  explicit UTC conversion.
- Issue #298 addresses explicit IANA timezone handling in the existing `Date`
  surface; issue #322 addresses immutable inputs and local calendar-day
  arithmetic. This operation does not change either contract.

## API

`Date::subtractUtcSeconds(DateTimeInterface $instant, mixed $seconds): string`

- Interpret the input as an **instant**, using its existing offset/timezone.
  Normalize a copy to UTC before subtraction; never mutate the input or its
  timezone object.
- Accept only a native, nonnegative integer number of seconds. Zero returns
  the same instant in UTC. Validate `is_int($seconds)` before arithmetic and
  reject negative or non-integer values with `InvalidArgumentException`;
  `DateTimeInterface` still constrains the instant. A typed `int $seconds`
  parameter would coerce floats, numeric strings, and booleans at a weakly
  typed caller boundary before method-body validation, so `mixed` is
  deliberate here. No implicit parse of a relative date string or duration.
- Subtract elapsed seconds, not local calendar days or months. DST transitions
  therefore have no special arithmetic rule beyond the input instant's offset.
- Return `DateTimeInterface::ATOM` text with `+00:00`, including at date, month,
  or year boundaries. The return is a value, not a `Date` or `DateTime` object.
- Accept instants and results in the four-digit ATOM year range 0001-9999;
  throw `OutOfRangeException` outside it rather than overflow or clamp.

The explicit `Utc` and `Seconds` names distinguish this from local calendar-day
arithmetic and from an unrestricted `DateInterval` (which can contain months,
years, or an inversion flag). A caller needing further date operations can
retain its source instant and construct an immutable value from the result.

## Test matrix

| Input instant | Seconds | Expected ATOM result | Concern |
| --- | ---: | --- | --- |
| `2024-01-01T00:00:00+00:00` | 0 | `2024-01-01T00:00:00+00:00` | identity and explicit UTC |
| `2024-03-01T00:00:00+02:00` | 1 | `2024-02-29T21:59:59+00:00` | offset and leap-day boundary |
| `2024-03-10T03:30:00-04:00` | 3600 | `2024-03-10T06:30:00+00:00` | DST-adjacent elapsed time |
| `2025-01-01T00:00:00+00:00` | 1 | `2024-12-31T23:59:59+00:00` | year boundary |

Tests should also assert unchanged source timestamp, timezone, and formatting;
reject negative integers, floats, numeric strings, booleans, null, and arrays
from weakly typed callers before arithmetic; and exercise a sufficiently large
integer duration across the implementation's supported range. No test should
depend on the process-default timezone or a live service.

This operation does not migrate existing `Date` methods. It uses elapsed
seconds rather than the local-calendar-day arithmetic of #322; callers should
choose the operation matching their temporal semantics.
