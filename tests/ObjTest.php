<?php
namespace BlueFission\Tests;

use BlueFission\Obj;
 
class ObjTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Obj';
 	protected $object;
	
	public function setUp(): void
	{
		$this->object = new static::$classname();
	}

	public function testEvaluatesAsStringUsingType()
	{
		$this->assertEquals(static::$classname, "".$this->object."");
	}

	public function testThrowsErrorOnUndefinedAccess()
	{
		// var_dump($this->object->testValue);
	}

	public function testAddsAndClearsUndefinedFields()
	{
		$this->object->testValue = true;
		$this->assertTrue($this->object->testValue);

		$this->object->clear();
		$this->assertEquals(null, $this->object->testValue);
	}
}