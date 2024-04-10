<?php
namespace BlueFission\Tests;

use BlueFission\Num;
 
class NumTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Num';
 	protected $object;
 	protected $blankObject;
 	protected $zeroObject;
 	protected $integerObject;
 	protected $largeObject;

	public function setUp(): void
	{
		$this->blankObject = new static::$classname;
		$this->zeroObject = new static::$classname(0);
		$this->integerObject = new static::$classname(1);
		$this->largeObject = new static::$classname(29);
	}

	// public function tearDown() {
	// 	//... clean up here
	// }
	
	// Default
	public function testValueReturnsAsNumeric()
	{
		$object = new Num('letters');
		$this->assertFalse(is_numeric($object->val()));
		$this->assertTrue(is_numeric($object()));
		// $this->assertTrue(is_numeric($object->cast()->val()));
	}

	public function testZeroAsValidNumber()
	{
		$this->assertTrue($this->zeroObject->isValid());
		$this->assertFalse($this->blankObject->isValid());
	}

	public function testPercentageReturnsCorrectValue()
	{
		$this->assertEquals(0.058, $this->largeObject->percentage(5));
	}
}