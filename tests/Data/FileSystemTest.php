<?php
namespace BlueFission\Tests\Data;

use BlueFission\Data\FileSystem;
 
class FileSystemTest extends \PHPUnit\Framework\TestCase {
 
	static $testdirectory = '../../testdirectory';

 	static $classname = 'BlueFission\Data\FileSystem';

 	protected $object;

 	static $configuration = [ 
 		'mode'=>'rw', 
 		'filter'=>[], 
 		'root'=>'../../testdirectory', 
 		'doNotConfirm'=>'false', 
 		'lock'=>false 
 	];
	
	public function setUp(): void
	{
		chdir(__DIR__);
		mkdir(static::$testdirectory);

		$this->object = new static::$classname(static::$configuration);
	}

	public function tearDown(): void
	{
		$testfiles = [
			'filesystem',
			'testfile.txt',
		];

		foreach ($testfiles as $file) {
			if (is_dir(static::$testdirectory.DIRECTORY_SEPARATOR.$file)) {
				@rmdir(static::$testdirectory.DIRECTORY_SEPARATOR.$file);
			}

			if (file_exists(static::$testdirectory.DIRECTORY_SEPARATOR.$file)) {
				@unlink(static::$testdirectory.DIRECTORY_SEPARATOR.$file);
			}
		}
	}

	public function testCanViewFolder()
	{
		touch(static::$testdirectory.DIRECTORY_SEPARATOR.'testfile.txt');

		$dir = $this->object->listDir();
		$status = $this->object->status();
		
		$this->assertEquals(['testfile.txt'], $dir);
		$this->assertEquals('Success', $status);
	}

	public function testCanCreateDirectory()
	{
		$this->object->mkdir('filesystem');

		$dir = $this->object->listDir();

		$this->assertTrue(count($dir) > 0);
	}

	public function testCanCreateFile()
	{
		$this->object->filename = 'testfile.txt';
		$this->object->write();

		// $status = $this->object->status();

		// $this->assertEquals('File \'testfile.txt\' has been created', $status);

		// $dir = $this->object->listDir();

		// $this->assertTrue(count($dir) > 0);
	}
}