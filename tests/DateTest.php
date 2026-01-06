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
