<?php
namespace BlueFission\Tests\Intelligence\Collections;

use BlueFission\Intelligence\Collections\OrganizedCollection;
 
class OrganizedCollectionTest extends \PHPUnit_Framework_TestCase {

 	static $classname = 'BlueFission\Intelligence\Collections\OrganizedCollection';

	public function setup()
	{
		$this->object = new static::$classname();
	}

	public function testCollectionMathIsAccurate()
	{
		$values = array(600, 470, 170, 430, 300);

		foreach ($values as $value) {
			$this->object->add($value);
		}
	
		$this->object->sort();
		$data = $this->object->data();

		$this->assertEquals(5, $data['count']);
		$this->assertEquals(5, $data['total']);
		$this->assertEquals(600, $data['max']);
		$this->assertEquals(170, $data['min']);
		$this->assertEquals(394, $data['mean1']);
		$this->assertEquals(21704, $data['variance1']);
		$this->assertEquals(147, $data['std1']);
		$this->assertEquals(21130, $data['variance2']);
		$this->assertEquals(164, $data['std2']);
	}
}