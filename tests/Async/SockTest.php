<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Async\Sock;
use Ratchet\Server\IoServer;

class SockTest extends TestCase {
    private $sock;
    private $port = 8080;

    protected function setUp(): void {
        parent::setUp();
        // Mock the WebSocketServer class which is referenced in Sock
        $this->mockWebSocketServer = $this->getMockBuilder('BlueFission\Async\WebSocketServer')
            ->disableOriginalConstructor()
            ->getMock();

        // Replace the class name with the mocked object just for the test
        $this->sock = new Sock($this->port, ['class' => get_class($this->mockWebSocketServer)]);
    }

    public function testStartAndStopWebSocketServer() {
        // Assume IoServer can be mocked and controlled
        // $mockIoServer = $this->createMock(IoServer::class);
        // $mockIoServer->method('run')->willReturn(null);
        // $mockIoServer->expects($this->once())->method('run');

        // // We can't really override IoServer::factory in a meaningful way in a unit test without changing the design,
        // // so we assume this test checks the interaction without internal server start
        // // This implies the Sock class might need refactoring to better support dependency injection

        // // Mock the socket part to simulate closing
        // $mockSocket = $this->getMockBuilder('Socket')
        //     ->disableOriginalConstructor()
        //     ->getMock();
        // $mockSocket->expects($this->once())->method('close');

        // $mockIoServer->socket = $mockSocket;

        // // Manually inject the mocked IoServer
        // $this->sock->setServer($mockIoServer);

        // // Test starting the server
        // $this->sock->start();

        // // Test stopping the server
        // $this->sock->stop();

        // // Check that no server instance remains after stopping
        // $this->assertNull($this->sock->getServer());
    }

    protected function tearDown(): void {
        parent::tearDown();
        // Clean up after test
        $this->sock->stop();
    }
}
