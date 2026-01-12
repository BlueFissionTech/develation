<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class CurlHeadersTest extends TestCase
{
    private string $location;

    protected function setUp(): void
    {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->markTestSkipped('Network tests are disabled');
        }

        $this->location = getenv('DEV_ELATION_CURL_TEST_URL') ?: 'https://www.bluefission.com';
        if (!HTTP::urlExists($this->location)) {
            $this->markTestSkipped('Curl target is not reachable');
        }
    }

    public function testGetRequestIncludesConfiguredHeaders(): void
    {
        $curl = new Curl([
            'target' => $this->location,
            'method' => 'get',
            'headers' => [
                'X-Curl-Test' => 'one',
                'Accept' => 'application/json',
            ],
        ]);
        $curl->option(CURLINFO_HEADER_OUT, true);

        $curl->open()->query();
        $headers = curl_getinfo($curl->connection(), CURLINFO_HEADER_OUT) ?: '';
        $normalized = strtolower($headers);
        $curl->close();

        $this->assertStringContainsString('x-curl-test: one', $normalized);
        $this->assertStringContainsString('accept: application/json', $normalized);
    }

    public function testPostRequestAddsJsonHeadersAndContentLength(): void
    {
        $payload = [
            'model' => 'dummy-model',
            'prompt' => 'hello',
        ];

        $curl = new Curl([
            'target' => $this->location,
            'method' => 'post',
            'headers' => [
                'X-Curl-Test' => 'two',
            ],
        ]);
        $curl->option(CURLINFO_HEADER_OUT, true);
        $curl->assign($payload);

        $curl->open()->query();
        $headers = curl_getinfo($curl->connection(), CURLINFO_HEADER_OUT) ?: '';
        $normalized = strtolower($headers);
        $curl->close();

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('x-curl-test: two', $normalized);
        $this->assertStringContainsString('content-type: application/json', $normalized);
        $this->assertStringContainsString('accept: application/json', $normalized);
        $this->assertStringContainsString('content-length: ' . strlen((string)$encoded), $normalized);
    }
}
