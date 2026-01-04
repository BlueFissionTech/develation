<?php

namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Data\Storage\Disk;

class DiskTest extends StorageTest
{
    public static $testdirectory = '../../../testdirectory';

    public static $classname = 'BlueFission\Data\Storage\Disk';

    public static $configuration = [ 'location' => '../../../testdirectory', 'name' => 'storage.tmp' ];

    protected $originalDir;

    public function setUp(): void
    {
        $this->originalDir = getcwd();
        chdir(__DIR__);

        $testDirPath = realpath(static::$testdirectory) ?: static::$testdirectory;
        if (!file_exists($testDirPath)) {
            mkdir($testDirPath, 0777, true); // Ensure the directory is created if it does not exist
        }
        // touch($testDirPath . DIRECTORY_SEPARATOR . 'storage.tmp'); // Ensure the file exists

        $this->object = new static::$classname(static::$configuration);
    }

    public function tearDown(): void
    {
        $testDirPath = realpath(static::$testdirectory) ?: static::$testdirectory;
        $testFilePath = $testDirPath . DIRECTORY_SEPARATOR . 'storage.tmp';
        if (file_exists($testFilePath)) {
            unlink($testFilePath); // Delete the file
        }
        chdir($this->originalDir); // Restore the original working directory
        unset($this->object);
    }

    #[RunInSeparateProcess]
    public function testStorageCanActivate()
    {
        parent::testStorageCanActivate();

        $this->assertEquals(Storage::STATUS_SUCCESSFUL_INIT, $this->object->status());
    }

    #[RunInSeparateProcess]
    public function testStorageCanWriteFields()
    {
        $this->object->activate();
        // die(var_dump($this->object->status()));
        parent::testStorageCanWriteFields();

        if (!file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp')) {
            $this->fail('File '.realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp not found.');
        }

        $this->assertEquals('{"var1":"checking","var2":"confirming"}', file_get_contents(static::$testdirectory.DIRECTORY_SEPARATOR.'storage.tmp'));
    }

    #[RunInSeparateProcess]
    public function testStorageCanWriteContentOverFields()
    {
        parent::testStorageCanWriteContentOverFields();

        if (!file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp')) {
            $this->fail('File '.realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp not found.');
        }

        $this->assertEquals('Testing.', file_get_contents(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.'storage.tmp'));
    }
}
