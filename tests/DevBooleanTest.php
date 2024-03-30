<?php
namespace BlueFission\Tests;

use BlueFission\DevBoolean;
 
class DevBooleanTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\DevBoolean';
	public function setup()
	{
		$this->blankObject = new static::$classname;
		$this->trueObject = new static::$classname(true);
		$this->falseObject = new static::$classname(false);
	}

	// public function tearDown() {
	// 	//... clean up here
	// }
	
	// Default
	public function testDefaultIsEmpty()
	{
		$trueResult = $this->blankObject->isEmpty();
		$falseResult = $this->blankObject->isNotEmpty();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testDefaultIsNotNull()
	{
		$trueResult = $this->blankObject->isNotNull();
		$falseResult = $this->blankObject->isNull();
	
		$this->assertTrue( $trueResult );
		$this->assertFalse( $falseResult );
	}

	public function testDefaultIsFalse()
	{
		$falseResult = $this->blankObject->value();
	
		$this->assertFalse( $falseResult );
	}

	public function testObjectYieldsOpposite()
	{
		$falseResult = $this->trueObject->opposite();

		$this->assertFalse( $falseResult );
		
		$trueResult = $this->falseObject->opposite();

		$this->assertTrue( $trueResult );
	}

	public function testObjectYieldsOppositeStatically()
	{
		$trueResult = DevBoolean::opposite(false);
		$this->assertTrue( $trueResult );

		$falseResult = DevBoolean::opposite(true);
		$this->assertFalse( $falseResult );

		$trueResult = DevBoolean::opposite(0);
		$this->assertTrue( $trueResult );

		$falseResult = DevBoolean::opposite(1);
		$this->assertFalse( $falseResult );

		$falseResult = DevBoolean::opposite('a');
		$this->assertFalse( $falseResult );

		$falseResult = DevBoolean::opposite(-3);
		$this->assertFalse( $falseResult );

	}
}