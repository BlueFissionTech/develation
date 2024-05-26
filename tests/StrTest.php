<?php
namespace BlueFission\Tests;

use BlueFission\Str;
 
class StrTest extends ValTest {
 
 	static $classname = 'BlueFission\Str';
 	protected $object;
	public function setUp(): void
	{
		$this->object = new Str('My Name Is John');
		$this->value = "Hello, World";

		$this->blankObject = new static::$classname();
		$this->nullObject = new static::$classname(null);
		$this->emptyObject = new static::$classname("");
		$this->zeroObject = new static::$classname(0);
		$this->valueObject = new static::$classname('foo');
	}

	public function testRandomStringSeldomRepeats()
	{
		$strings = [];
		for ($i = 0; $i < 100; $i++ ) {
			$string = $this->object->clear()->random()->val();
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

	public function testSetsValue()
	{
		$object = new static::$classname();
		$object->val('foo');
		$this->assertEquals('foo', $object->val());
	}

	public function testClearsValue()
	{
		$object = new static::$classname('foobar');
		$object->clear();
		$this->assertNull($object->val());
	}

	public function testSetsReference()
	{
		$object = new static::$classname();
		$value = 'foo';
		$object->ref($value);
		$this->assertEquals('foo', $object->val());
		$value = 'bar';
		$this->assertEquals('bar', $object->val());
		$object->val('foobar');
		$this->assertEquals('foobar', $value);
	}

	public function testSnapshotsValue()
	{
		$object = new static::$classname('foo');
		$object->snapshot();
		$this->assertEquals('foo', $object->val());
		$object->val('bar');
		$this->assertEquals('bar', $object->val());
		$object->reset();
		$this->assertEquals('foo', $object->val());
	}

	public function testClearsSnapshot()
	{
		$object = new static::$classname('foo');
		$object->snapshot();
		$this->assertEquals('foo', $object->val());
		$object->clearSnapshot();
		$object->val('bar');
		$this->assertEquals('bar', $object->val());
		$object->reset();
		$this->assertEquals(null, $object->val());
	}

	public function testGetsDelta()
	{
		$object = new static::$classname('foo');
		$object->snapshot();
		$this->assertEquals('foo', $object->val());
		$object->val('bar');
		$this->assertEquals('bar', $object->val());
		$this->assertEquals(1.0, $object->delta());
	}

	public function testsTagsAndGroups()
	{
		// Tagging strings
		$string1 = static::$classname::make('Hello, ')->tag('greet');
		$string3 = static::$classname::make('Goodbye'); // Not tagged as greet
		$string2 = static::$classname::make('World')->tag('greet');

		// Retrieving all greet values
		$greetings = static::$classname::grp('greet');

		$greeting = '';
		foreach ($greetings as $greet) {
		    $greeting .= $greet();
		}

		$this->assertEquals('Hello, World', $greeting);
	}
}