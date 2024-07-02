<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
use BlueFission\Connections\Socket;

class SocketTest extends ConnectionTest {
 
    static $classname = 'BlueFission\Connections\Socket';
    static $canbetested = true;

    public function setUp(): void
    {
        parent::setUp();
        static::$configuration = [
            'target' => 'https://bluefission.com', // Use a valid target that can be used for testing
            'port' => 80
        ];
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
        static::$configuration['target'] = 'http://nonexistent12345.com'; // Unreachable host
        $this->object->open();
        $this->assertEquals(Connection::STATUS_NOTCONNECTED, $this->object->status(), "Socket should fail to connect to an invalid host.");
    }
}
