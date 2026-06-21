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

	public function testStaticFileExistsChecksConcretePathWithoutConstructingStorage()
	{
		$path = $this->testdirectory.DIRECTORY_SEPARATOR.'wrapper.php';
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing.php';
		touch($path);

		$this->assertTrue(FileSystem::fileExists($path));
		$this->assertFalse(FileSystem::fileExists($missing));
		$this->assertFalse(FileSystem::fileExists($this->testdirectory));
		$this->assertFileDoesNotExist($missing);
	}

	public function testReadOnlyExistsProbeAcceptsAssociativeConfigArray()
	{
		$path = $this->testdirectory.DIRECTORY_SEPARATOR.'existing.txt';
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing.txt';
		touch($path);

		$filesystem = new FileSystem([
			'root' => $this->testdirectory,
			'filter' => [],
			'doNotConfirm' => true,
		]);

		$this->assertTrue($filesystem->exists($path));
		$this->assertFalse($filesystem->exists($missing));
		$this->assertFileDoesNotExist($missing);
	}

	public function testLinesReadsFileContentsAsIterableValues()
	{
		$path = $this->testdirectory.DIRECTORY_SEPARATOR.'names.txt';
		file_put_contents($path, 'Ada'.PHP_EOL.'Grace');

		$filesystem = new FileSystem($path);

		$this->assertSame(['Ada', 'Grace'], $filesystem->lines());
	}

	public function testLinesCanSplitInMemoryContents()
	{
		$filesystem = new FileSystem([
			'root' => $this->testdirectory,
			'filter' => [],
			'doNotConfirm' => true,
		]);
		$filesystem->contents('alpha|beta|gamma');

		$this->assertSame(['alpha', 'beta', 'gamma'], $filesystem->lines('|'));
	}

	public function testLinesReturnsEmptyListForMissingFileWithoutCreatingIt()
	{
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing-lines.txt';
		$filesystem = new FileSystem($missing);

		$this->assertSame([], $filesystem->lines());
		$this->assertFileDoesNotExist($missing);
	}

	public function testEntriesReturnsSortedDirectoryValues()
	{
		touch($this->testdirectory.DIRECTORY_SEPARATOR.'zeta.txt');
		touch($this->testdirectory.DIRECTORY_SEPARATOR.'alpha.txt');

		$filesystem = new FileSystem([
			'root' => $this->testdirectory,
			'filter' => [],
			'doNotConfirm' => true,
		]);

		$this->assertSame(['alpha.txt', 'zeta.txt'], $filesystem->entries());
	}

	public function testEntriesReturnsEmptyListForMissingDirectoryWithoutCreatingIt()
	{
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing-directory';
		$filesystem = new FileSystem([
			'root' => $missing,
			'filter' => [],
			'doNotConfirm' => true,
		]);

		$this->assertSame([], $filesystem->entries());
		$this->assertDirectoryDoesNotExist($missing);
	}
}
