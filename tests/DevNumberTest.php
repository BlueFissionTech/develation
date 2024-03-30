<?php
namespace BlueFission\Tests;

use BlueFission\DevNumber;
 
class DevNumberTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\DevNumber';
	public function setup()
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
		$object = new DevNumber('letters');
		$this->assertTrue(is_numeric($object->value()));
	}

	public function testZeroAsValidNumber()
	{
		$this->assertTrue($this->zeroObject->isValid());
		$this->assertTrue($this->blankObject->isValid());
	}

	public function testBlankAsInvalidNumberWhenNotAllowed()
	{
		$this->assertFalse($this->zeroObject->isValid(false));
		$this->assertFalse($this->blankObject->isValid(false));
	}

	public function testPercentageReturnsCorrectValue()
	{
		$this->assertEquals(0.058, $this->largeObject->percentage(5));
	}
}