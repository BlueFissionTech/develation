<?php
namespace BlueFission\Tests\Collections;

use BlueFission\Collections\Group;
 
class GroupTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Collections\Group';

	public function setUp(): void
	{
		$this->object = new static::$classname();
	}

	public function testConversionOfAddedItems()
	{
		$array = array(
			'entry1'=>array(
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			),
			'entry2'=>array(
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			),
			'entry3'=>array(
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			),
			'entry4'=>array(
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			),
		);

		$group = new Group( $array );

		$group->type('\BlueFission\Behavioral\Programmable');

		$object = $group->get('entry2');

		$this->assertEquals('BlueFission\Behavioral\Programmable', get_class($object));

		$this->assertEquals(2, $object->item2);
	}
}