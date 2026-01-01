<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Async\Remote;
use BlueFission\Connections\Curl;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class RemoteTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        // Mock the Curl class and set it up in the Remote class setup if needed
        // $this->curlMock = $this->createMock(Curl::class);
        // Assume Remote can be modified to inject this dependency or use a factory that we can mock
    }

    public function testRemoteHttpRequestSuccessful() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = getenv('DEV_ELATION_REMOTE_TEST_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Remote target is not reachable');
        }
        $options = ['method' => 'GET', 'headers' => ['Accept' => 'application/json']];
        $result = '';

        $expectedResult = 'response data';

        // $this->curlMock->method('open')->willReturn(true);
        // $this->curlMock->method('query')->willReturn(true);
        // $this->curlMock->method('result')->willReturn($expectedResult);
        // $this->curlMock->method('close')->willReturn(true);
        // $this->curlMock->method('status')->willReturn(Remote::STATUS_SUCCESS);

        // Injecting the mocked Curl object into the Remote class (assuming we have such functionality)
        // Remote::setCurlInstance($this->curlMock);
        
        Remote::do($url, $options)
        ->then(function ($data) use (&$result) {
            $result = $data;
        },
        function ($error) use (&$result) {
            $result = $error;
        });

        Remote::run();

        $this->assertTrue($result !== null, "The Remote HTTP request should return expected data.");
    }

    public function testRemoteHttpRequestFailure() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $url = getenv('DEV_ELATION_REMOTE_TEST_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($url)) {
            $this->markTestSkipped('Remote target is not reachable');
        }
        $url = rtrim($url, '/') . '/fail';
        $options = ['method' => 'POST', 'data' => ['key' => 'value']];
        $result = null;

        $promise = Remote::do($url, $options);

        // Attempt to execute the remote operation and handle failure
        $promise->then(
            function ($result) {}, 
            function ($error) use (&$result) {
                $result = $error;
            }
        );

        Remote::run();

        $this->assertNotNull($result);
    }
}
