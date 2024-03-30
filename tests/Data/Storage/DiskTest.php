<?php
namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Disk;
 
class DiskTest extends StorageTest {
 
	static $testdirectory = '../../../testdirectory';

 	static $classname = 'BlueFission\Data\Storage\Disk';

 	static $configuration = array( 'location'=>__DIR__.'/../../../testdirectory', 'name'=>'storage.tmp' );
	
	public function setUp(): void
	{
		chdir(__DIR__);
		// die(var_dump(__DIR__.'/../../../testdirectory'));
		$this->object = new static::$classname(static::$configuration);
	}

	public function tearDown()
	{
		$testfiles = array(
			'storage.tmp',
		);

		foreach ($testfiles as $file) {
			if (is_dir(static::$testdirectory.DIRECTORY_SEPARATOR.$file))
				@rmdir(static::$testdirectory.DIRECTORY_SEPARATOR.$file);

			if (file_exists(static::$testdirectory.DIRECTORY_SEPARATOR.$file))
				@unlink(static::$testdirectory.DIRECTORY_SEPARATOR.$file);
		}
	}

	public function testStorageCanWriteContentOverFields()
	{
		parent::testStorageCanWriteContentOverFields();

		$this->assertEquals('Testing.', file_get_contents(__DIR__.'/../../../testdirectory/storage.tmp'));

		unset($this->object);
	}

	public function testStorageCanWriteFields()
	{
		parent::testStorageCanWriteFields();

		$this->assertEquals('{"var1":"checking","var2":"confirming"}', file_get_contents(__DIR__.'/../../../testdirectory/storage.tmp'));

		unset($this->object);
	}
}