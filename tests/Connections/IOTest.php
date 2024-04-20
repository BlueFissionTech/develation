<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\IO;
use PHPUnit\Framework\TestCase;

class IOTest extends TestCase {
    public function testStdio() {
        // Mock Stdio to simulate input behavior
        $mockStdio = $this->createMock(\BlueFission\Connections\Stdio::class);
        $mockStdio->method('open')->willReturnSelf();
        $mockStdio->method('result')->willReturn("Test input data");

        // Replace the real Stdio with our mock
        // Assuming you have a way to set the internal Stdio instance, you would set it here
        // IO::setStdioInstance($mockStdio);
        
        //write a text file with the contents 'test'
        chdir(__DIR__);
        $filename = '../../testdirectory/testfile.txt';
        file_put_contents($filename, 'test');

        $data = IO::std($filename);

        unlink($filename);

        $this->assertEquals($data, 'test');
    }

    public function testFetch() {
        $url = 'https://bluefission.com';
        $mockCurl = $this->createMock(\BlueFission\Connections\Curl::class);
        $mockCurl->method('open')->willReturnSelf();
        $mockCurl->method('result')->willReturn("Fetched data");

        // Replace the real Curl with our mock
        // IO::setCurlInstance($mockCurl);

        $data = IO::fetch($url);

        $this->assertTrue($data !== null);
    }

    public function testStream() {
        $url = 'https://bluefission.com/stream';
        $mockStream = $this->createMock(\BlueFission\Connections\Stream::class);
        $mockStream->method('open')->willReturnSelf();
        $mockStream->method('result')->willReturn("Streamed data");

        // IO::setStreamInstance($mockStream);

        $data = IO::stream($url);

        $this->assertTrue($data !== null);
    }

    public function testSock() {
        $url = 'ws://example.com/socket';
        $mockSocket = $this->createMock(\BlueFission\Connections\Socket::class);
        $mockSocket->method('open')->willReturnSelf();
        $mockSocket->method('result')->willReturn("Socket data");

        // IO::setSocketInstance($mockSocket);
        
        $data = IO::sock($url);

        $this->assertTrue($data !== null);
    }
}
