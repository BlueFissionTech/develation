<?php
namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Data\Storage\Disk;
 
class DiskTest extends StorageTest {
 
	static $testdirectory = '../../../testdirectory';

 	static $classname = 'BlueFission\Data\Storage\Disk';

 	static $configuration = [ 'location'=>'../../../testdirectory', 'name'=>'storage.tmp' ];

	public function setUp(): void
	{
		chdir(__DIR__);

		if (!file_exists(realpath(static::$testdirectory))) {
			mkdir(static::$testdirectory);
		}
		// touch(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp');

		$this->object = new static::$classname(static::$configuration);
	}

	public function tearDown(): void
	{
		$testfiles = [
			'storage.tmp',
		];

		foreach ($testfiles as $file) {
			if (is_dir(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file)) {
				@rmdir(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file);
			}

			if (file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file)) {
				@unlink(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file);
			}
		}
		
		unset($this->object);
	}

	public function testStorageCanActivate()
	{
		parent::testStorageCanActivate();

		$this->assertEquals(Storage::STATUS_SUCCESSFUL_INIT, $this->object->status());
	}

	public function testStorageCanWriteFields()
	{
		parent::testStorageCanWriteFields();

		if (!file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp')) {
			$this->fail('File '.realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp not found.');
		}

		$this->assertEquals('{"var1":"checking","var2":"confirming"}', file_get_contents(static::$testdirectory.DIRECTORY_SEPARATOR.'storage.tmp'));
	}

	public function testStorageCanWriteContentOverFields()
	{
		parent::testStorageCanWriteContentOverFields();

		if (!file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp')) {
			$this->fail('File '.realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp not found.');
		}

		$this->assertEquals('Testing.', file_get_contents(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp'));
	}
}