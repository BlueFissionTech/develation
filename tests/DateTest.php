<?php
namespace BlueFission\Tests;

use BlueFission\Date;
 
class DateTest extends ValTest {

	static $classname = 'BlueFission\Date';
 	protected $object;
 	protected $stringObject;
 	protected $dateObject;
 	protected $timeObject;

	public function setUp(): void
	{
		$this->value = '2012-12-12T00:00:00+00:00';

		$this->object = new static::$classname;
		$this->stringObject = new static::$classname('2012-12-12');
		$this->dateObject = new static::$classname(new \DateTime('2012-12-12'));
		$this->timeObject = new static::$classname(strtotime('2012-12-12'));


		$this->blankObject = new static::$classname();
		$this->nullObject = new static::$classname(null);
		$this->emptyObject = new static::$classname("");
		$this->zeroObject = new static::$classname(0);
		$this->valueObject = new static::$classname('December 12, 2012');
	}

	public function testConstructionCreatesDate()
	{
		$this->assertEquals(date('c'), $this->object->val());
		$this->assertEquals('2012-12-12T00:00:00+00:00', $this->stringObject->val());
		$this->assertEquals('2012-12-12T00:00:00+00:00', $this->dateObject->val());
		$this->assertEquals('2012-12-12T00:00:00+00:00', $this->timeObject->val());
		$this->assertEquals('2012-12-12T00:00:00+00:00', $this->valueObject->val());
	}

	public function testSetsValue()
	{
		$object = new static::$classname();
		$object->val('2012-12-12');
		$this->assertEquals('2012-12-12T00:00:00+00:00', $object->val());
	}

	public function testClearsValue()
	{
		$object = new static::$classname('2012-12-12');
		$object->clear();
		$this->assertEquals('1970-01-01T00:00:00+00:00', $object->val());
	}

	public function testSetsReference()
	{
		$object = new static::$classname();
		$value = '2012-12-12';
		$object->ref($value);
		$this->assertEquals('2012-12-12T00:00:00+00:00', $object->val());
		$value = '2012-12-13';
		$this->assertEquals('2012-12-13T00:00:00+00:00', $object->val());
		$object->val('2012-12-14');
		$this->assertEquals('2012-12-14', $value);
	}

	public function testSnapshotsValue()
	{
		$object = new static::$classname('2012-12-12');
		$object->snapshot();
		$this->assertEquals('2012-12-12T00:00:00+00:00', $object->val());
		$object->val('2012-12-13');
		$this->assertEquals('2012-12-13T00:00:00+00:00', $object->val());
		$object->reset();
		$this->assertEquals('2012-12-12T00:00:00+00:00', $object->val());
	}

	public function testClearsSnapshot()
	{
		$object = new static::$classname('2012-12-12');
		$object->snapshot();
		$this->assertEquals('2012-12-12T00:00:00+00:00', $object->val());
		$object->clearSnapshot();
		$object->val('2012-12-13');
		$this->assertEquals('2012-12-13T00:00:00+00:00', $object->val());
		$object->reset();
		$this->assertEquals('1970-01-01T00:00:00+00:00', $object->val());
	}

	public function testGetsDelta()
	{
		$object = new static::$classname('2012-12-12');
		$object->snapshot();
		$this->assertEquals('2012-12-12T00:00:00+00:00', $object->val());
		$object->val('2012-12-13');
		$this->assertEquals('2012-12-13T00:00:00+00:00', $object->val());
		$this->assertEquals(86400.0, $object->delta());
	}

	public function testCastsAsType()
	{
		$object = new static::$classname('now');
		$this->assertSame('datetime', $object->getType());
		$value = $object->cast()->val();
		$this->assertIsString($value);
		$this->assertNotEmpty($value);
		$this->assertInstanceOf(\DateTime::class, new \DateTime($value));
	}

	public function testGetsTimestamp()
	{
		$object = new static::$classname('2012-12-12');
		$this->assertEquals(strtotime('2012-12-12'), $object->timestamp());
	}

	public function testExplicitTimezoneTimestampIsIndependentOfProcessDefault()
	{
		$original = date_default_timezone_get();
		$zone = new \DateTimeZone('America/New_York');
		$expected = (new \DateTimeImmutable('2026-03-09 09:00:00', $zone))->getTimestamp();

		try {
			foreach (['UTC', 'Asia/Tokyo', 'America/New_York'] as $default) {
				date_default_timezone_set($default);
				$date = new Date('2026-03-09 09:00:00', 'America/New_York');
				$this->assertSame($expected, $date->timestamp());
				$this->assertSame('2026-03-09T09:00:00-04:00', $date->val());
				$this->assertSame('2026-03-09', $date->date());
				$this->assertSame('09:00:00', $date->time());
				$this->assertSame('2026-03-09 09:00:00 America/New_York',
					$date->format('Y-m-d H:i:s e')->val());
				$this->assertSame($expected, $date->timestamp());
				$this->assertSame($expected, $date->timestamp('2026-03-09 09:00:00'));
				$this->assertSame($default, date_default_timezone_get());
			}
		} finally {
			date_default_timezone_set($original);
		}
	}

	public function testExplicitTimezoneUsesNativeTransitionResolution()
	{
		$original = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$zone = new \DateTimeZone('America/New_York');

		try {
			foreach ([
				['2026-03-08 00:00:00', '2026-03-09 00:00:00', 23],
				['2026-11-01 00:00:00', '2026-11-02 00:00:00', 25],
			] as [$start, $end, $hours]) {
				$this->assertSame($hours * 3600,
					(new Date($end, 'America/New_York'))->timestamp()
					- (new Date($start, 'America/New_York'))->timestamp());
			}

			foreach (['2026-03-08 02:30:00', '2026-11-01 01:30:00'] as $wallTime) {
				$native = new \DateTimeImmutable($wallTime, $zone);
				$date = new Date($wallTime, 'America/New_York');
				$this->assertSame($native->getTimestamp(), $date->timestamp());
				$this->assertSame($native->format('c'), $date->val());
			}
			$this->assertSame('UTC', date_default_timezone_get());
		} finally {
			date_default_timezone_set($original);
		}
	}

	public function testExplicitTimezonePreservesTimestampInputAndExistingDateTimeInstant()
	{
		$original = date_default_timezone_get();
		date_default_timezone_set('UTC');

		try {
			$epoch = 1773061200;
			$this->assertSame($epoch, (new Date($epoch, 'America/New_York'))->timestamp());
			$source = new \DateTime('2026-03-09 09:00:00', new \DateTimeZone('Asia/Tokyo'));
			$this->assertSame($source->getTimestamp(), (new Date($source))->timestamp());
			$this->assertSame('Asia/Tokyo', (new Date($source))->datetime->getTimezone()->getName());
			$this->assertSame($source->getTimestamp(), (new Date($source, 'America/New_York'))->timestamp());
			$this->assertSame('Asia/Tokyo', (new Date($source, 'America/New_York'))->datetime->getTimezone()->getName());
			$this->assertSame('UTC', date_default_timezone_get());
		} finally {
			date_default_timezone_set($original);
		}
	}

	public function testAbsentTimezoneStillUsesProcessDefaultAtConstruction()
	{
		$original = date_default_timezone_get();

		try {
			foreach (['UTC', 'Asia/Tokyo'] as $default) {
				date_default_timezone_set($default);
				$native = new \DateTimeImmutable('2026-03-09 09:00:00');
				$date = new Date('2026-03-09 09:00:00');
				$this->assertSame($native->getTimestamp(), $date->timestamp());
				$this->assertSame($native->format('c'), $date->val());
				$this->assertSame($default, date_default_timezone_get());
			}
		} finally {
			date_default_timezone_set($original);
		}
	}

	public function testGetsDateTime()
	{
		$object = new static::$classname('2012-12-12');
		$this->assertEquals(new \DateTime('2012-12-12'), $object->datetime);
	}

	public function testGetsDate()
	{
		$object = new static::$classname('2012-12-12');
		$this->assertEquals('2012-12-12', $object->date());
	}

	public function testFormatsTimestamp()
	{
		$timestamp = strtotime('2012-12-12 12:00:00');

		$this->assertSame(date('Y-m-d', $timestamp), Date::formatTimestamp($timestamp));
		$this->assertSame(date('c', $timestamp), Date::formatTimestamp((string)$timestamp, 'c'));
		$this->assertSame('2012', (new Date('2012-12-12 12:00:00'))->formatTimestamp('Y'));
	}

	public function testGetsTime()
	{
		$object = new static::$classname('2012-12-12');
		$this->assertEquals('00:00:00', $object->time());

		$object = new static::$classname('2012-12-12 12:12:12');
		$this->assertEquals('12:12:12', $object->time());
	}

	public function testRecognizesBlankAsNull()
	{
		$falseResult = $this->blankObject->isNull();
		$trueResult = $this->blankObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsNull()
	{
		$falseResult = $this->nullObject->isNull();
		$trueResult = $this->nullObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testDoesntRecognizeEmptyAsNull()
	{
		$falseResult = $this->emptyObject->isNull();
		$trueResult = $this->emptyObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeZeroAsNull()
	{
		$falseResult = $this->zeroObject->isNull();
		$trueResult = $this->zeroObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsNull()
	{
		$falseResult = $this->valueObject->isNull();
		$trueResult = $this->valueObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	// Empty Test
	public function testRecognizesBlankAsEmpty()
	{
		$falseResult = $this->blankObject->isEmpty();
		$trueResult = $this->blankObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesNullAsEmpty()
	{
		$falseResult = $this->nullObject->isEmpty();
		$trueResult = $this->nullObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testRecognizesEmptyasEmpty()
	{
		$falseResult = $this->emptyObject->isEmpty();
		$trueResult = $this->emptyObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeZeroAsEmpty()
	{
		$trueResult = $this->zeroObject->isNotEmpty();
		$falseResult = $this->zeroObject->isEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testAccurateFalsiness()
	{
		$falseResult = $this->blankObject->isFalsy();
		$this->assertFalse( $falseResult );
	
		$falseResult = $this->nullObject->isFalsy();
		$this->assertFalse( $falseResult );
	
		$falseResult = $this->emptyObject->isFalsy();
		$this->assertFalse( $falseResult );
	
		$falseResult = $this->zeroObject->isFalsy();
		$this->assertFalse( $falseResult );
	}
	
	public function testRecognizesZeroAsEmptyStatically()
	{
		$trueResult = static::$classname::isNotEmpty(0);
		$falseResult = static::$classname::isEmpty(0);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
	public function testDoesntRecognizeValueAsEmptyStatically()
	{
		for ($i = 1; $i<100; $i++) {
			$falseResult = static::$classname::isEmpty($i);
			$trueResult = static::$classname::isNotEmpty($i);
		
			$this->assertTrue( $trueResult );
			$this->assertFalse( $falseResult );
		}
	}

	public function testRecognizesBlankAsEmptyStatically()
	{
		$falseResult = static::$classname::isEmpty();
		$trueResult = static::$classname::isNotEmpty();

		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testIsReturnsFalseForArbitraryString()
	{
		$this->assertFalse(Date::is('2022_08_03_000000_scaffold_framework_tables.php'));
	}

	public function testIsReturnsTrueForSupportedDateValues()
	{
		$this->assertTrue(Date::is('2012-12-12'));
		$this->assertTrue(Date::is((string)strtotime('2012-12-12')));
		$this->assertTrue(Date::is(new \DateTime('2012-12-12')));
	}
	
	public function testRecognizesNullAsEmptyStatically()
	{
		$falseResult = static::$classname::isEmpty(null);
		$trueResult = static::$classname::isNotEmpty(null);
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testRecognizesEmptyasEmptyStatically()
	{
		$falseResult = static::$classname::isEmpty("");
		$trueResult = static::$classname::isNotEmpty("");
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}
	
}
