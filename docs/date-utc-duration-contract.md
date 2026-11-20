# UTC duration subtraction contract (proposal)

This is a review proposal for issue #272, not a shipped API. It is deliberately
separate from the existing mutable `Date` value operations and from scheduling
policy.

## Existing surface and constraints

- `Date` wraps a mutable `DateTime`. Its constructor accepts a `DateTime` or
  parseable value, and its default formatted value uses `c`.
- `Date::diff()` reports an absolute numeric difference. It cannot express a
  directional, immutable subtraction result.
- `Date::formatTimestamp()` uses PHP's process-default timezone. It is not an
  explicit UTC conversion.
- Issue #298 addresses explicit IANA timezone handling in the existing `Date`
  surface; issue #322 addresses immutable inputs and local calendar-day
  arithmetic. This proposal must not silently change either contract.

## Proposed opt-in operation for review

`Date::subtractUtcSeconds(DateTimeInterface $instant, int $seconds): string`

- Interpret the input as an **instant**, using its existing offset/timezone.
  Normalize a copy to UTC before subtraction; never mutate the input or its
  timezone object.
- Accept nonnegative, whole seconds only. Zero returns the same instant in
  UTC. Reject negative seconds with `InvalidArgumentException`; PHP's declared
  types reject unsupported instant/duration types. No implicit parse of a
  relative date string or floating duration.
- Subtract elapsed seconds, not local calendar days or months. DST transitions
  therefore have no special arithmetic rule beyond the input instant's offset.
- Return `DateTimeInterface::ATOM` text with `+00:00`, including at date, month,
  or year boundaries. The return is a value, not a `Date` or `DateTime` object.
- Reject out-of-range results rather than overflow or silently clamp. The
  implementation should explicitly test the supported platform range and
  document any narrower limit if one is required.

The explicit `Utc` and `Seconds` names distinguish this from local calendar-day
arithmetic and from an unrestricted `DateInterval` (which can contain months,
years, or an inversion flag). A separate object-returning helper may be more
useful if callers need further date operations; that is a review question, not
an additional proposed API in this slice.

## Proposed test matrix (not yet implemented)

| Input instant | Seconds | Expected ATOM result | Concern |
| --- | ---: | --- | --- |
| `2024-01-01T00:00:00+00:00` | 0 | `2024-01-01T00:00:00+00:00` | identity and explicit UTC |
| `2024-03-01T00:00:00+02:00` | 1 | `2024-02-29T21:59:59+00:00` | offset and leap-day boundary |
| `2024-03-10T03:30:00-04:00` | 3600 | `2024-03-10T06:30:00+00:00` | DST-adjacent elapsed time |
| `2025-01-01T00:00:00+00:00` | 1 | `2024-12-31T23:59:59+00:00` | year boundary |

Tests should also assert unchanged source timestamp, timezone, and formatting;
reject negative duration; and exercise a sufficiently large duration near the
implementation's supported range. No test should depend on the process-default
timezone or a live service.

## Review gates

1. Approve or revise the public signature, especially string versus immutable
   object return and the accepted instant input type.
2. Confirm that fixed elapsed seconds, rather than calendar components, match
   the intended duration semantics.
3. Reconcile the timezone behavior with #298 and the immutable input behavior
   with #322 before implementation. No migration of existing `Date` methods is
   implied.

This document alone does not satisfy issue #272's behavioral acceptance
criteria; implementation, tests, and review are still required.
