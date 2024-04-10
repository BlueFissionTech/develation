<?php
namespace BlueFission\Tests;

use BlueFission\Flag;
 
class FlagTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Flag';
 	protected $object;

 	protected $blankObject;
 	protected $trueObject;
 	protected $falseObject;

	public function setUp(): void
	{
		$this->blankObject = new static::$classname;
		$this->trueObject = new static::$classname(true);
		$this->falseObject = new static::$classname(false);
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
}