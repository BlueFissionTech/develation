<?php

namespace BlueFission\Tests;

use BlueFission\Obj;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes the trusted native serialization contract independently of Mem.
 */
class ObjSerializationTest extends TestCase
{
    public function testFreshObjectRoundTripsThroughNativeSerialization(): void
    {
        $original = new Obj();

        // This payload is produced locally, never supplied by an untrusted source.
        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertInstanceOf(Obj::class, $restored);
    }
}
