<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
use BlueFission\Connections\IO;
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

    public function testReadInputReadsExplicitStream()
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'request-body');
        rewind($stream);

        $this->assertSame('request-body', Stdio::readInput($stream));

        fclose($stream);
    }

    public function testReadInputReturnsEmptyStringForEmptyStream()
    {
        $stream = fopen('php://temp', 'r+');

        $this->assertSame('', Stdio::readInput($stream));

        fclose($stream);
    }

    public function testInputAliasAvoidsInteractiveStreamPolling()
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'alias-body');
        rewind($stream);

        $this->assertSame('alias-body', Stdio::input($stream));

        fclose($stream);
    }

    public function testInputReturnsEmptyStringForUnreadableSource()
    {
        $this->assertSame('', Stdio::input(''));
        $this->assertSame('', Stdio::input(__DIR__ . '/missing-input.txt'));
    }

    public function testReadLineReadsOneLineFromExplicitStream()
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "attack\nwait\n");
        rewind($stream);

        $this->assertSame("attack\n", Stdio::readLine($stream));
        $this->assertSame("wait\n", Stdio::readLine($stream));

        fclose($stream);
    }

    public function testReadLineReturnsEmptyStringForUnreadableSource()
    {
        $this->assertSame('', Stdio::readLine(''));
        $this->assertSame('', Stdio::readLine(__DIR__ . '/missing-line-input.txt'));
    }

    public function testIoInputDelegatesToSafeStdioRead()
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'io-body');
        rewind($stream);

        $this->assertSame('io-body', IO::input($stream));

        fclose($stream);
    }

    // public function testStatusAfterClose()
    // {
    //     if (!static::$canbetested) return;
    //     $this->object->open();
    //     $this->object->close();

    //     $this->assertEquals(Connection::STATUS_DISCONNECTED, $this->object->status(), "Status should be disconnected after closing.");
    // }
}
