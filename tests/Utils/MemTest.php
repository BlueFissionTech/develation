<?php

namespace BlueFission\Utils\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Utils\Mem;

class MemTest extends TestCase
{
    public function setUp(): void
    {
        Mem::flush(); // Ensure a clean slate before each test
    }

    public function testRegisterAndGet()
    {
        $object = new \stdClass();
        $object->name = "TestObject";
        $id = spl_object_hash($object);

        Mem::register($object, $id);
        $retrieved = Mem::get($id);

        $this->assertSame($object, $retrieved, "The object retrieved should be the same as the object registered.");
    }

    public function testFlushRemovesUnusedObjects()
    {
        $object = new \stdClass();
        $id = spl_object_hash($object);
        Mem::threshold(1); // Set threshold to 1 second
        Mem::register($object, $id);

        // Simulate passage of time and not using the object
        sleep(1); // Sleeping to simulate time pass, adjust based on threshold
        Mem::flush();

        $this->assertNull(Mem::get($id), "The object should be flushed from memory after being unused.");
    }

    public function testAuditReportsUnusedObjects()
    {
        $object = new \stdClass();
        $id = spl_object_hash($object);
        Mem::register($object, $id);

        $unused = Mem::audit();
        $this->assertArrayHasKey($id, $unused, "The object should be reported as unused.");

        Mem::get($id); // Use the object
        $unusedAfterUse = Mem::audit();
        $this->assertArrayNotHasKey($id, $unusedAfterUse, "The object should no longer be reported as unused after use.");
    }

    public function testWakeupAndSleep()
    {
        chdir(__DIR__); // Change to current directory (since Mem uses relative paths to save audit

        $object = new \stdClass();
        $id = spl_object_hash($object);
        $storage = new \BlueFission\Data\Storage\Disk([
            'location' => '../../testdirectory',
            'name' => 'mempool.txt'
        ]);
        $storage->activate();
        Mem::setStorage($storage);
        Mem::threshold(300); // Set threshold to 300 seconds
        Mem::register($object, $id);

        Mem::sleep($id);
        $audit = Mem::audit();
        $this->assertFalse($audit[$id]['used'], "The object should be marked as not used after sleep.");

        $object = Mem::get($id);
        $this->assertTrue(empty($object), "The object should not be available before wakeup.");

        Mem::wakeup($id);
        $object = Mem::get($id);
        $this->assertFalse(empty($object), "The object should be available after wakeup.");

    }
}
