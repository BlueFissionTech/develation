<?php
namespace BlueFission\Tests;

use BlueFission\DevString;
 
class DevStringTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\DevString';
	public function setUp(): test
	{
		$this->object = new DevString('My Name Is John');
	}

	public function testRandomStringSeldomRepeats()
	{
		$strings = array();
		for ($i = 0; $i < 100; $i++ ) {
			$string = $this->object->random();
			$this->assertFalse(in_array($string, $strings));
			$strings[] = $string;
		}
	}

	public function testSimilarityMethodWorks()
	{
		$sim = $this->object->similarityTo('My Name Is John');
		$this->assertEquals(1, $sim);

		$sim = $this->object->similarityTo('My Name Is Jon');
		$this->assertTrue($sim < 1);
	}
}