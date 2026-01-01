<?php
namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\Event;
 
class StorageTest extends \PHPUnit\Framework\TestCase {
 
	static $testdirectory = '../../testdirectory';

 	static $classname = 'BlueFission\Data\Storage\Storage';

 	static $configuration = [];

 	protected $object;
	
	public function setUp(): void
	{
		$this->object = new static::$classname(static::$configuration);
	}

	public function testStorageCanActivate()
	{
		$value = false;
		$this->object->when(Event::FAILURE, function($b, $args) use (&$value) {
			$value = true;
		})->activate();

		$this->assertTrue($value);
	}

	public function testStorageCanRead()
	{
		$this->object->contents('read-test');
		$this->object->write();
		$this->object->read();
		$this->assertEquals('read-test', $this->object->contents());
	}

	public function testStorageCanWriteFields()
	{
		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->write();
		$this->object->read();
		$data = json_decode($this->object->contents(), true);
		$this->assertEquals('checking', $data['var1'] ?? null);
		$this->assertEquals('confirming', $data['var2'] ?? null);
	}

	public function testStorageCanWriteContentOverFields()
	{
		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->contents("Testing.");
		$this->object->write();
		$this->object->read();
		$this->assertEquals('Testing.', $this->object->contents());
	}
}
