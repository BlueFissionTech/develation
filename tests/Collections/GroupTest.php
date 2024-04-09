<?php
namespace BlueFission\Tests\Collections;

use BlueFission\Collections\Group;
use BlueFission\Collections\Collection;
 
class GroupTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Collections\Group';
 	protected $object;

	public function setUp(): void
	{
		$array = [
			'entry1'=>[
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			],
			'entry2'=>[
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			],
			'entry3'=>[
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			],
			'entry4'=>[
				'item1'=>1,
				'item2'=>2,
				'item3'=>3,
			],
		];

		$this->object = new static::$classname($array);
	}

	public function testConversionOfAddedItems()
	{
		$group = $this->object;

		if (!($group instanceof Collection)) {
			$this->fail("Group is not an instance of Collection");
		}

		$group->type('BlueFission\Obj');

		$object = $group->get('entry2');

		$this->assertEquals('BlueFission\Obj', get_class($object));

		$this->assertEquals(2, $object->item2);
	}
}