<?php
namespace BlueFission\Tests;

use BlueFission\Arr;
 
class ArrTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Arr';
 	protected $object;

	public function setUp(): void
	{
		$this->object = new static::$classname('First Item');
	}

	public function testConstructionCreatesCountableIndex()
	{
		$this->assertEquals('First Item', $this->object[0]);
	}

	public function testAppendingWithBlankOffset()
	{
		$this->object[] = 'Second Item';
		$this->assertEquals('Second Item', $this->object[1]);
	}

	public function testAppendingWithNumericOffset()
	{
		$this->object[] = 'Second Item';
		$this->object[3] = 'Third Item';
		$this->assertEquals('Third Item', $this->object[3]);
	}

	public function testNumericIndicesArentAssociative()
	{
		$this->assertTrue($this->object->isIndexed());
		$this->assertFalse($this->object->isAssoc());
	}

	public function testAppendingWithAlphaOffset()
	{
		$this->object[] = 'Second Item';
		$this->object[3] = 'Third Item';
		$this->object['four'] = 'Fourth Item';
		$this->assertEquals('Fourth Item', $this->object['four']);
	}

	public function testMixedIndicesAreAssociative()
	{

		$this->object[] = 'Second Item';
		$this->object[3] = 'Third Item';
		$this->object['four'] = 'Fourth Item';
		
		$this->assertFalse($this->object->isIndexed());
		$this->assertTrue($this->object->isAssoc());
	}
}