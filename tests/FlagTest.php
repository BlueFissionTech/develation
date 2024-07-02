<?php
namespace BlueFission\Tests;

use BlueFission\Flag;
 
class FlagTest extends ValTest {
 
 	static $classname = 'BlueFission\Flag';
 	protected $object;

 	protected $blankObject;
 	protected $trueObject;
 	protected $falseObject;

	public function setUp(): void
	{
		$this->value = true;

		$this->blankObject = new static::$classname;
		$this->trueObject = new static::$classname(true);
		$this->falseObject = new static::$classname(false);


		$this->blankObject = new static::$classname();
		$this->nullObject = new static::$classname(null);
		$this->emptyObject = new static::$classname("");
		$this->zeroObject = new static::$classname(0);
		$this->valueObject = new static::$classname(1);
	}

	// public function tearDown() {
	// 	//... clean up here
	// }
	
	// Default
	public function testDefaultIsNotEmpty()
	{
		$falseResult = $this->blankObject->isNotEmpty();
		$trueResult = $this->blankObject->isEmpty();

		$this->assertFalse( $falseResult );
		$this->assertTrue( $trueResult );
	}

	public function testDefaultIsNotNull()
	{
		$trueResult = $this->blankObject->isNull();
		$falseResult = $this->blankObject->isNotNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testDefaultIsFalse()
	{
		$falseResult = $this->blankObject->cast()->val();
	
		$this->assertFalse( $falseResult );
	}

	public function testObjectYieldsOpposite()
	{
		$falseResult = $this->trueObject->flip()->val();

		$this->assertFalse( $falseResult );
		
		$trueResult = $this->falseObject->flip()->val();

		$this->assertTrue( $trueResult );
	}

	public function testObjectYieldsOppositeStatically()
	{
		$trueResult = Flag::flip(false);
		$this->assertTrue( $trueResult );

		$falseResult = Flag::flip(true);
		$this->assertFalse( $falseResult );

		$trueResult = Flag::flip(0);
		$this->assertTrue( $trueResult );

		$falseResult = Flag::flip(1);
		$this->assertFalse( $falseResult );

		$falseResult = Flag::flip('a');
		$this->assertFalse( $falseResult );

		$falseResult = Flag::flip(-3);
		$this->assertFalse( $falseResult );

	}

	public function testSetsValue()
	{
		$object = new static::$classname();
		$object->val(true);
		$this->assertEquals(true, $object->val());
	}

	public function testClearsValue()
	{
		$object = new static::$classname(true);
		$object->clear();
		$this->assertNull($object->val());
	}

	public function testSetsReference()
	{
		$object = new static::$classname();
		$value = true;
		$object->ref($value);
		$this->assertEquals(true, $object->val());
		$value = false;
		$this->assertEquals(false, $object->val());
		$object->val(true);
		$this->assertEquals(true, $value);
	}

	public function testSnapshotsValue()
	{
		$object = new static::$classname(true);
		$object->snapshot();
		$this->assertEquals(true, $object->val());
		$object->val(false);
		$this->assertEquals(false, $object->val());
		$object->reset();
		$this->assertEquals(true, $object->val());
	}

	public function testClearsSnapshot()
	{
		$object = new static::$classname(true);
		$object->snapshot();
		$this->assertEquals(true, $object->val());
		$object->clearSnapshot();
		$object->val(false);
		$this->assertEquals(false, $object->val());
		$object->reset();
		$this->assertEquals(null, $object->val());
	}

	public function testGetsDelta()
	{
		$object = new static::$classname(true);
		$object->snapshot();
		$this->assertEquals(true, $object->val());
		$object->val(false);
		$this->assertEquals(false, $object->val());
		$this->assertEquals(true, $object->delta());
	}


	public function testRecognizesEmptyasEmpty()
	{
		$falseResult = $this->emptyObject->isEmpty();
		$trueResult = $this->emptyObject->isNotEmpty();
	
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