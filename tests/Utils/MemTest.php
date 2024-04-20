<?php

namespace BlueFission\Utils\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Utils\Mem;

class MemTest extends TestCase
{
    public function setUp(): void {
        Mem::flush(); // Ensure a clean slate before each test
    }

    public function testRegisterAndGet() {
        $object = new \stdClass();
        $object->name = "TestObject";
        $id = spl_object_hash($object);

        Mem::register($object, $id);
        $retrieved = Mem::get($id);

        $this->assertSame($object, $retrieved, "The object retrieved should be the same as the object registered.");
    }

    public function testFlushRemovesUnusedObjects() {
        $object = new \stdClass();
        $id = spl_object_hash($object);
        Mem::register($object, $id);

        // Simulate passage of time and not using the object
        sleep(1); // Sleeping to simulate time pass, adjust based on threshold
        Mem::flush();

        $this->assertNull(Mem::get($id), "The object should be flushed from memory after being unused.");
    }

    public function testAuditReportsUnusedObjects() {
        $object = new \stdClass();
        $id = spl_object_hash($object);
        Mem::register($object, $id);

        $unused = Mem::audit();
        $this->assertArrayHasKey($id, $unused, "The object should be reported as unused.");

        Mem::get($id); // Use the object
        $unusedAfterUse = Mem::audit();
        $this->assertArrayNotHasKey($id, $unusedAfterUse, "The object should no longer be reported as unused after use.");
    }

    public function testWakeupAndSleep() {
        $object = new \stdClass();
        $id = spl_object_hash($object);
        Mem::register($object, $id);

        Mem::sleep($id);
        $audit = Mem::audit();
        $this->assertFalse($audit[$id]['used'], "The object should be marked as not used after sleep.");

        Mem::wakeup($id);
        $audit = Mem::audit();
        $this->assertTrue($audit[$id]['used'], "The object should be marked as used after wakeup.");
    }
}
