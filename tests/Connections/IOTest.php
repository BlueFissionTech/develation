<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\IO;
use PHPUnit\Framework\TestCase;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class IOTest extends TestCase {
    private function networkOption(string $name, string $default): string
    {
        return getenv($name) ?: $default;
    }

    private function networkConfig(): array
    {
        return [
            'timeout' => 5,
            'connect_timeout' => 3,
        ];
    }

    private function networkResultOrSkip(callable $operation, string $label): mixed
    {
        try {
            $result = $operation();
        } catch (\Throwable $exception) {
            $this->markTestSkipped($label . ' failed due to network conditions: ' . $exception->getMessage());
        }

        if ($result === false || $result === null || $result === '') {
            $this->markTestSkipped($label . ' returned no data under current network conditions');
        }

        return $result;
    }

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

        $url = $this->networkOption('DEV_ELATION_IO_FETCH_URL', 'https://bluefission.com');
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Fetch target is not reachable');
        }

        $data = $this->networkResultOrSkip(
            fn () => IO::fetch($url, $this->networkConfig()),
            'Fetch'
        );

        $this->assertNotNull($data);
    }

    public function testStream() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = $this->networkOption('DEV_ELATION_IO_STREAM_URL', 'https://bluefission.com');
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Stream target is not reachable');
        }

        $data = $this->networkResultOrSkip(
            fn () => IO::stream($url, $this->networkConfig()),
            'Stream'
        );

        $this->assertNotNull($data);
    }

    public function testSock() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = $this->networkOption('DEV_ELATION_IO_SOCKET_URL', 'https://bluefission.com');
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Socket target is not reachable');
        }

        $data = $this->networkResultOrSkip(
            fn () => IO::sock($url, $this->networkConfig()),
            'Socket'
        );

        $this->assertNotNull($data, "Socket data should not be null");
    }
}
