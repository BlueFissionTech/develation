<?php
namespace BlueFission\Tests\Collections;

use BlueFission\Collections\Hierarchical;
 
class HierarchicalTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Collections\Hierarchical';

	public function setup()
	{
		$this->object = new static::$classname();
		$this->object->label('main');
	}

	public function testGetChildPath()
	{
		$object = new Hierarchical();
		$this->object->add($object, 'child');
		$path = array('main','child');

		$this->assertEquals($path, $object->path());
	}

	public function testGetFamilyPath()
	{
		$object = new Hierarchical();
		$object2 = new Hierarchical();

		$object->add($object2, 'sub');

		$this->object->add($object, 'child');

		$path = array('main','child','sub');

		$this->assertEquals($path, $object2->path());
	}
}