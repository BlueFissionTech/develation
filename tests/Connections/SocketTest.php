<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
use BlueFission\Connections\Socket;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class SocketTest extends ConnectionTest
{
    public static $classname = 'BlueFission\Connections\Socket';
    public static $canbetested = true;

    public function setUp(): void
    {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $target = getenv('DEV_ELATION_SOCKET_TEST_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($target)) {
            $this->markTestSkipped('Socket target is not reachable');
        }

        static::$configuration = [
            'target' => $target,
            'port' => 80
        ];

        parent::setUp();
    }

    public function testOpenConnection()
    {
        $this->object->open();
        $this->assertEquals(Connection::STATUS_CONNECTED, $this->object->status(), "Socket should connect successfully.");
    }

    public function testQuery()
    {
        $this->object->open();
        $this->object->query("GET / HTTP/1.1\r\nHost: bluefission.com\r\n\r\n");
        $this->assertNotEmpty($this->object->result(), "Query should return non-empty result.");
        $this->assertEquals(Connection::STATUS_SUCCESS, $this->object->status(), "Query should be successful.");
    }

    public function testCloseConnection()
    {
        $this->object->open();
        $this->object->close();
        $this->assertEquals(Connection::STATUS_DISCONNECTED, $this->object->status(), "Socket should be disconnected successfully.");
    }

    public function testFailToConnect()
    {
        static::$configuration['target'] = 'http://nonexistent.invalid';
        $this->object->config(static::$configuration);
        $this->object->open();
        $this->assertEquals(Connection::STATUS_FAILED, $this->object->status(), "Socket should fail to connect to an invalid host.");
    }
}
