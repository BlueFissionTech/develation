<?php
namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
 
abstract class StorageTest extends \PHPUnit_Framework_TestCase {
 
	static $testdirectory = '../../testdirectory';

 	static $classname = 'BlueFission\Data\Storage\Storage';

 	static $configuration = array( );
	
	public function setup()
	{
		$this->object = new static::$classname(static::$configuration);
	}

	public function testStorageCanActivate()
	{
		$this->object->activate();

		$this->assertEquals(Storage::STATUS_SUCCESSFUL_INIT, $this->object->status());
	}

	public function testStorageCanWriteContentOverFields()
	{
		$this->object->activate();

		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->contents("Testing.");
		$this->object->write();
	}

	public function testStorageCanWriteFields()
	{
		$this->object->activate();

		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->write();
	}
}