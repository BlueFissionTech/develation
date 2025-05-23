<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
use BlueFission\Connections\Stdio;

class StdioTest extends ConnectionTest
{
    public static $classname = 'BlueFission\Connections\Stdio';
    public static $canbetested = true;

    public function setUp(): void
    {
        parent::setUp();
    }

    // public function testReadFromStdin()
    // {
    //     if (!static::$canbetested) return;
    //     $this->object->open();

    //     // Simulate input for STDIN for testing purposes
    //     fwrite(STDIN, "test input\n");
    //     rewind(STDIN);

    //     $this->assertEquals("test input\n", $this->object->query()->result(), "Should read 'test input' from stdin");
    // }

    public function testConnectionStatusOnOpenInput()
    {
        if (!static::$canbetested) {
            return;
        }
        $this->object->open();

        $this->assertEquals(Connection::STATUS_CONNECTED, $this->object->status(), "Status should be connected after opening input.");
    }

    public function testConnectionStatusOnOpenOutput()
    {
        if (!static::$canbetested) {
            return;
        }
        $this->object->open();

        $this->assertEquals(Connection::STATUS_CONNECTED, $this->object->status(), "Status should be connected after opening output.");
    }

    // public function testStatusAfterClose()
    // {
    //     if (!static::$canbetested) return;
    //     $this->object->open();
    //     $this->object->close();

    //     $this->assertEquals(Connection::STATUS_DISCONNECTED, $this->object->status(), "Status should be disconnected after closing.");
    // }
}
