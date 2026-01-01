<?php
namespace BlueFission\Tests\Data;

use BlueFission\Data\FileSystem;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';
 
class FileSystemTest extends \PHPUnit\Framework\TestCase {
 
	private string $testdirectory;

 	static $classname = 'BlueFission\Data\FileSystem';

 	protected $object;

 	static $configuration = [ 
 		'mode'=>'c+', 
 		'filter'=>[], 
 		'root'=>'', 
 		'doNotConfirm'=>'false', 
 		'lock'=>false 
 	];
	
	public function setUp(): void
	{
		$this->testdirectory = TestEnvironment::tempDir('bf_fs');
		static::$configuration['root'] = $this->testdirectory;
		$this->object = new static::$classname(static::$configuration);
	}

	public function tearDown(): void
	{
		TestEnvironment::removeDir($this->testdirectory);
	}

	public function testCanViewFolder()
	{
		touch($this->testdirectory.DIRECTORY_SEPARATOR.'testfile.txt');

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

		$this->assertTrue(file_exists($this->testdirectory.DIRECTORY_SEPARATOR.'testfile.txt'));
	}
}
