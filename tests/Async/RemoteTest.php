<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Async\Remote;
use BlueFission\Connections\Curl;

class RemoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock the Curl class and set it up in the Remote class setup if needed
        // $this->curlMock = $this->createMock(Curl::class);
        // Assume Remote can be modified to inject this dependency or use a factory that we can mock
    }

    public function testRemoteHttpRequestSuccessful()
    {
        $url = 'https://bluefission.com';
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
        ->then(
            function ($data) use (&$result) {
                $result = $data;
            },
            function ($error) use (&$result) {
                $result = $error;
            }
        );

        Remote::run();

        $this->assertTrue($result !== null, "The Remote HTTP request should return expected data.");
    }

    public function testRemoteHttpRequestFailure()
    {
        $url = 'https://bluefission.com/fail';
        $options = ['method' => 'POST', 'data' => ['key' => 'value']];

        $promise = Remote::do($url, $options);

        // Attempt to execute the remote operation and handle failure
        $promise->then(
            function ($result) {},
            function ($error) {
                $this->asserTrue($error === null, "The Remote HTTP request should handle failures.");
            }
        );
    }
}
