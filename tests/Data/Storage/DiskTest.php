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

	public function testFreshReadModifyWriteKeepsJsonFields(): void
	{
		$path = $this->tempDir . DIRECTORY_SEPARATOR . 'storage.tmp';
		file_put_contents($path, '{"marker":{"value":"before"},"unchanged":"keep"}');
		$this->object->activate();
		$this->object->read();
		$this->assertSame(
			['marker' => ['value' => 'before'], 'unchanged' => 'keep'],
			$this->object->contents()
		);
		$this->object->assign(['marker' => ['value' => 'after']])->write();

		$this->assertSame(
			['marker' => ['value' => 'after'], 'unchanged' => 'keep'],
			json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)
		);

		$this->object->read();
		$this->assertSame(
			['marker' => ['value' => 'after'], 'unchanged' => 'keep'],
			$this->object->contents()
		);
	}

	public function testExplicitRawContentsAfterReadStillOverridesFields(): void
	{
		$path = $this->tempDir . DIRECTORY_SEPARATOR . 'storage.tmp';
		file_put_contents($path, '{"marker":"before"}');
		$this->object->activate();
		$this->object->read();
		$this->object->contents('raw replacement');
		$this->object->assign(['marker' => 'after'])->write();

		$this->assertSame('raw replacement', file_get_contents($path));
	}

	public function testEmptyJsonObjectCanGainFieldsWithoutChangingItsShape(): void
	{
		$path = $this->tempDir . DIRECTORY_SEPARATOR . 'storage.tmp';
		file_put_contents($path, '{}');
		$this->object->activate()->read()->write();
		$this->assertSame('{}', file_get_contents($path));

		$this->object->assign(['marker' => 'added'])->write();
		$this->assertSame('{"marker":"added"}', file_get_contents($path));
	}

	public function testScalarJsonReadRemainsRawWithoutFieldAssignment(): void
	{
		$path = $this->tempDir . DIRECTORY_SEPARATOR . 'storage.tmp';
		file_put_contents($path, 'null');
		$this->object->activate()->read()->write();
		$this->assertSame('null', file_get_contents($path));
	}
}
