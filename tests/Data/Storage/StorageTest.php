<?php
namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\Event;
 
class StorageTest extends \PHPUnit\Framework\TestCase {
 
	static $testdirectory = '../../testdirectory';

 	static $classname = 'BlueFission\Data\Storage\Storage';

 	static $configuration = array( );

 	protected $object;
	
	public function setUp(): void
	{
		$this->object = new static::$classname(static::$configuration);
	}

	public function testStorageCanActivate()
	{
		$value = false;
		$this->object->behavior(Event::ACTIVATED, function($b, $args) use (&$value) {
			$value = true;
		});
		$this->object->activate();

		$this->assertTrue($value);
	}

	public function testStorageCanRead()
	{
		$this->object->activate();

		$this->object->read();
	}

	public function testStorageCanWriteFields()
	{
		$this->object->activate();

		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->write();
	}

	public function testStorageCanWriteContentOverFields()
	{
		$this->object->activate();
		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->contents("Testing.");
		$this->object->write();
	}
}