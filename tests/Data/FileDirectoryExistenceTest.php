<?php
namespace BlueFission\Tests\Data;

use BlueFission\Data\Directory;
use BlueFission\Data\File;
use BlueFission\Data\FileSystem;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class FileDirectoryExistenceTest extends \PHPUnit\Framework\TestCase
{
    private string $testdirectory;

    public function setUp(): void
    {
        $this->testdirectory = TestEnvironment::tempDir('bf_file_dir_exists');
    }

    public function tearDown(): void
    {
        TestEnvironment::removeDir($this->testdirectory);
    }

    public function testFileReportsExistingAndMissingPathsWithoutCreatingFiles()
    {
        $path = $this->testdirectory . DIRECTORY_SEPARATOR . 'sample.txt';
        $missing = $this->testdirectory . DIRECTORY_SEPARATOR . 'missing.txt';
        touch($path);

        $file = new File();

        $this->assertTrue($file->exists($path));
        $this->assertTrue($file->isReachable($path));
        $this->assertFalse($file->exists($missing));
        $this->assertFalse($file->isReachable($missing));
        $this->assertFileDoesNotExist($missing);
    }

    public function testFileCanUseItsHierarchicalLabelAsPath()
    {
        $path = $this->testdirectory . DIRECTORY_SEPARATOR . 'labelled.txt';
        touch($path);

        $file = new File();
        $file->label($path);

        $this->assertTrue($file->exists());
    }

    public function testDirectoryReportsExistingAndMissingPathsWithoutCreatingDirectories()
    {
        $missing = $this->testdirectory . DIRECTORY_SEPARATOR . 'missing';
        $directory = new class(new FileSystem(['root' => $this->testdirectory, 'filter' => []])) extends Directory {};

        $this->assertTrue($directory->exists($this->testdirectory));
        $this->assertTrue($directory->isReachable($this->testdirectory));
        $this->assertFalse($directory->exists($missing));
        $this->assertFalse($directory->isReachable($missing));
        $this->assertDirectoryDoesNotExist($missing);
    }

    public function testDirectoryCanUseItsHierarchicalLabelAsPath()
    {
        $directory = new class(new FileSystem(['root' => $this->testdirectory, 'filter' => []])) extends Directory {};
        $directory->label($this->testdirectory);

        $this->assertTrue($directory->exists());
    }
}
