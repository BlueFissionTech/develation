<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\IO;
use PHPUnit\Framework\TestCase;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class IOTest extends TestCase {
    public function testStdio() {
        $dir = TestEnvironment::tempDir('bf_stdio');
        $filename = $dir . DIRECTORY_SEPARATOR . 'testfile.txt';
        $outputFile = $dir . DIRECTORY_SEPARATOR . 'out.txt';
        file_put_contents($filename, 'test');

        $data = IO::std($filename, ['output' => $outputFile]);

        $this->assertEquals('test', $data);
        TestEnvironment::removeDir($dir);
    }

    public function testFetch() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = getenv('DEV_ELATION_IO_FETCH_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Fetch target is not reachable');
        }

        $data = IO::fetch($url);

        $this->assertNotNull($data);
    }

    public function testStream() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = getenv('DEV_ELATION_IO_STREAM_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Stream target is not reachable');
        }

        $data = IO::stream($url);

        $this->assertNotNull($data);
    }

    public function testSock() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = getenv('DEV_ELATION_IO_SOCKET_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Socket target is not reachable');
        }

        $data = IO::sock($url);

        $this->assertNotNull($data, "Socket data should not be null");
    }
}
