<?php
namespace BlueFission\Tests\Data;

use BlueFission\Data\FileSystem;
 
class FileSystemTest extends \PHPUnit_Framework_TestCase {
 
	static $testdirectory = '../../testdirectory';

 	static $classname = 'BlueFission\Data\FileSystem';

 	static $configuration = array( 'mode'=>'rw', 'filter'=>array('..','.htm','.html','.pl','.txt'), 'root'=>'../../testdirectory', 'doNotConfirm'=>'false', 'lock'=>false );
	
	public function setup()
	{
		chdir(__DIR__);
		$this->object = new static::$classname(static::$configuration);
	}

	public function tearDown()
	{
		$testfiles = array(
			'filesystem',
			'testfile.txt',
		);

		foreach ($testfiles as $file) {
			if (is_dir(static::$testdirectory.DIRECTORY_SEPARATOR.$file))
				rmdir(static::$testdirectory.DIRECTORY_SEPARATOR.$file);

			if (file_exists(static::$testdirectory.DIRECTORY_SEPARATOR.$file))
				unlink(static::$testdirectory.DIRECTORY_SEPARATOR.$file);
		}
	}

	public function testCanViewFolder()
	{
		$dir = $this->object->listDir();
		$status = $this->object->status();
		$this->assertEquals(array(), $dir);
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
		
		$dir = $this->object->listDir();
		$this->assertTrue(count($dir) > 0);
	}
}