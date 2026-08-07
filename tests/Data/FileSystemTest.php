<?php
namespace BlueFission\Tests\Data;

use BlueFission\Arr;
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

	public function testCanViewFolderWithConfiguredExtensionFilter()
	{
		touch($this->testdirectory.DIRECTORY_SEPARATOR.'keep.txt');
		touch($this->testdirectory.DIRECTORY_SEPARATOR.'skip.log');

		$this->object->filter(['.txt']);
		$dir = $this->object->listDir();

		$this->assertEquals(['keep.txt'], $dir);
	}

	public function testCanCreateDirectory()
	{
		$this->object->mkdir('filesystem');

		$dir = $this->object->listDir();

		$this->assertTrue(Arr::count($dir) > 0);
	}

	public function testCanCreateNestedDirectoryRecursively()
	{
		$this->object->mkdir('one'.DIRECTORY_SEPARATOR.'two'.DIRECTORY_SEPARATOR.'three', true);

		$this->assertTrue(FileSystem::directoryExists($this->testdirectory.DIRECTORY_SEPARATOR.'one'.DIRECTORY_SEPARATOR.'two'.DIRECTORY_SEPARATOR.'three'));
		$this->assertSame('Directory created successfully', $this->object->status());
	}

	public function testCreateDirectoryReportsExistingDirectory()
	{
		mkdir($this->testdirectory.DIRECTORY_SEPARATOR.'existing');

		$this->object->mkdir('existing', true);

		$this->assertSame('Directory already exists', $this->object->status());
	}

	public function testCreateDirectoryRejectsTargetsOutsideConfiguredRoot()
	{
		$this->object->mkdir('..'.DIRECTORY_SEPARATOR.'outside-root', true);

		$this->assertSame('Location is outside of allowed path.', $this->object->status());
		$this->assertFalse(FileSystem::directoryExists(Arr::join([FileSystem::pathDirectory($this->testdirectory), 'outside-root'], DIRECTORY_SEPARATOR)));
	}

	public function testCanCreateFile()
	{
		$this->object->filename = 'testfile.txt';
		$this->object->write();

		$this->assertTrue(FileSystem::fileExists($this->testdirectory.DIRECTORY_SEPARATOR.'testfile.txt'));
	}

	public function testCanCopyFileToDirectory()
	{
		$source = $this->testdirectory.DIRECTORY_SEPARATOR.'source.txt';
		$targetDirectory = $this->testdirectory.DIRECTORY_SEPARATOR.'target';
		mkdir($targetDirectory);
		file_put_contents($source, 'copy me');

		$this->object->copy($targetDirectory, $source);

		$this->assertSame('Successfully copied file', $this->object->status());
		$this->assertSame('copy me', FileSystem::fileContents($targetDirectory.DIRECTORY_SEPARATOR.'source.txt'));
	}

	public function testMoveRemovesOriginalFile()
	{
		$source = $this->testdirectory.DIRECTORY_SEPARATOR.'move.txt';
		$targetDirectory = $this->testdirectory.DIRECTORY_SEPARATOR.'moved';
		mkdir($targetDirectory);
		file_put_contents($source, 'move me');

		$this->object->move($targetDirectory, $source);

		$this->assertFileDoesNotExist($source);
		$this->assertSame('move me', FileSystem::fileContents($targetDirectory.DIRECTORY_SEPARATOR.'move.txt'));
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

	public function testStaticDirectoryExistsChecksConcretePathWithoutConstructingStorage()
	{
		$directory = $this->testdirectory.DIRECTORY_SEPARATOR.'nested';
		$file = $this->testdirectory.DIRECTORY_SEPARATOR.'sample.txt';
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing';
		mkdir($directory);
		touch($file);

		$this->assertTrue(FileSystem::directoryExists($directory));
		$this->assertFalse(FileSystem::directoryExists($file));
		$this->assertFalse(FileSystem::directoryExists($missing));
		$this->assertDirectoryDoesNotExist($missing);
	}

	public function testStaticIsReadableChecksConcretePathWithoutConstructingStorage()
	{
		$path = $this->testdirectory.DIRECTORY_SEPARATOR.'readable.txt';
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing-readable.txt';
		touch($path);

		$this->assertTrue(FileSystem::isReadable($path));
		$this->assertTrue(FileSystem::isReadable($this->testdirectory));
		$this->assertFalse(FileSystem::isReadable($missing));
		$this->assertFileDoesNotExist($missing);
	}

	public function testStaticFileContentsReadsConcretePathWithoutConstructingStorage()
	{
		$path = $this->testdirectory.DIRECTORY_SEPARATOR.'contents.txt';
		$missing = $this->testdirectory.DIRECTORY_SEPARATOR.'missing-contents.txt';
		file_put_contents($path, 'file contents');

		$this->assertSame('file contents', FileSystem::fileContents($path));
		$this->assertNull(FileSystem::fileContents($missing));
		$this->assertFileDoesNotExist($missing);
	}

	public function testStaticFileBasenameReturnsConcretePathBasename()
	{
		$path = $this->testdirectory.DIRECTORY_SEPARATOR.'basename.txt';

		$this->assertSame('basename.txt', FileSystem::fileBasename($path));
		$this->assertNull(FileSystem::fileBasename(null));
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
