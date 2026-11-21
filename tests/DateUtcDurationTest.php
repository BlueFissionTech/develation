<?php

namespace BlueFission\Tests;

use BlueFission\Date;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

class DateUtcDurationTest extends TestCase
{
    public function testSubtractsElapsedSecondsFromAnImmutableInstantInUtc(): void
    {
        $cases = [
            ['2024-01-01T00:00:00+00:00', 0, '2024-01-01T00:00:00+00:00'],
            ['2024-03-01T00:00:00+02:00', 1, '2024-02-29T21:59:59+00:00'],
            ['2024-03-10T03:30:00-04:00', 3600, '2024-03-10T06:30:00+00:00'],
            ['2025-01-01T00:00:00+00:00', 1, '2024-12-31T23:59:59+00:00'],
        ];

        foreach ($cases as [$input, $seconds, $expected]) {
            $instant = new DateTimeImmutable($input);
            $this->assertSame($expected, Date::subtractUtcSeconds($instant, $seconds));
            $this->assertSame($input, $instant->format(DateTimeImmutable::ATOM));
        }
    }

    public function testMutableInputIsNotChanged(): void
    {
        $instant = new DateTime('2024-03-10T03:30:00-04:00');
        $before = $instant->format('Y-m-d\TH:i:sP e');

        $this->assertSame('2024-03-10T06:30:00+00:00', Date::subtractUtcSeconds($instant, 3600));
        $this->assertSame($before, $instant->format('Y-m-d\TH:i:sP e'));
    }

    public function testNamedTimezoneDaylightSavingTransitionUsesElapsedSeconds(): void
    {
        $instant = new DateTimeImmutable('2024-03-10 03:30:00', new \DateTimeZone('America/New_York'));
        $this->assertSame('2024-03-10T06:30:00+00:00', Date::subtractUtcSeconds($instant, 3600));
        $this->assertSame('2024-03-10T03:30:00-04:00', $instant->format(DateTimeImmutable::ATOM));
        $this->assertSame('America/New_York', $instant->getTimezone()->getName());
    }

    public function testRejectsNonIntegerAndNegativeDurationsWithoutCoercion(): void
    {
        $instant = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        foreach ([-1, 1.5, '1', true, null, []] as $seconds) {
            try {
                Date::subtractUtcSeconds($instant, $seconds);
                $this->fail('Unsupported duration was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testRejectsResultsOutsideSupportedAtomYearRange(): void
    {
        $instant = new DateTimeImmutable('0001-01-01T00:00:00+00:00');
        $this->expectException(OutOfRangeException::class);
        Date::subtractUtcSeconds($instant, 1);
    }

    public function testAcceptsLargeDurationWithinSupportedRange(): void
    {
        $instant = new DateTimeImmutable('9999-12-31T23:59:59+00:00');
        $this->assertSame('0001-01-01T00:00:00+00:00', Date::subtractUtcSeconds($instant, 315537897599));
    }

    public function testRejectsDurationGreaterThanSupportedSpan(): void
    {
        $instant = new DateTimeImmutable('9999-12-31T23:59:59+00:00');
        $this->expectException(OutOfRangeException::class);
        Date::subtractUtcSeconds($instant, PHP_INT_MAX);
    }
}
