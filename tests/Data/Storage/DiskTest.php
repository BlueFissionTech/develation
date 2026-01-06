<?php
namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Data\Storage\Disk;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../../Support/TestEnvironment.php';
 
class DiskTest extends StorageTest {
 
 	static $classname = 'BlueFission\Data\Storage\Disk';

 	static $configuration = [ 'location'=>'', 'name'=>'storage.tmp' ];

	private string $tempDir;

	public function setUp(): void
	{
	    $this->tempDir = TestEnvironment::tempDir('bf_disk');
	    static::$configuration['location'] = $this->tempDir;
	    $this->object = new static::$classname(static::$configuration);
	}

	public function tearDown(): void
	{
	    TestEnvironment::removeDir($this->tempDir);
	    unset($this->object);
	}

	public function testStorageCanActivate()
	{
		$this->object->activate();
		$this->assertEquals(Storage::STATUS_SUCCESSFUL_INIT, $this->object->status());
	}

	public function testStorageCanWriteFields()
	{
		$this->object->activate();
		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->write();

		$filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'storage.tmp';
		if (!file_exists($filePath)) {
			$this->fail('File ' . $filePath . ' not found.');
		}

		$this->assertEquals('{"var1":"checking","var2":"confirming"}', file_get_contents($filePath));

		$this->object->read();
		$this->assertEquals(['var1' => 'checking', 'var2' => 'confirming'], $this->object->contents());
	}

	public function testStorageCanWriteContentOverFields()
	{
		$this->object->activate();
		$this->object->var1 = 'checking';
		$this->object->var2 = 'confirming';
		$this->object->contents("Testing.");
		$this->object->write();

		$filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'storage.tmp';
		if (!file_exists($filePath)) {
			$this->fail('File ' . $filePath . ' not found.');
		}

		$this->assertEquals('Testing.', file_get_contents($filePath));

		$this->object->read();
		$this->assertEquals('Testing.', $this->object->contents());
	}
}
