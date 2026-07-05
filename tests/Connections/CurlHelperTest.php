<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
use PHPUnit\Framework\TestCase;

class CurlHelperProbe extends Curl
{
    public function headers($headers): array
    {
        return $this->normalizeHeaders($headers);
    }

    public function jsonHeaders(array $headers, string $payload): array
    {
        return $this->jsonPayloadHeaders($headers, $payload);
    }
}

class CurlHelperTest extends TestCase
{
    public function testNormalizeHeadersCastsAssociativeValues(): void
    {
        $curl = new CurlHelperProbe();

        $headers = $curl->headers([
            'Accept' => ['application/json', 'text/plain'],
            'X-Test' => 'one',
        ]);

        $this->assertSame([
            'Accept: application/json, text/plain',
            'X-Test: one',
        ], $headers);
    }

    public function testJsonPayloadHeadersPreserveExistingCaseInsensitiveHeaders(): void
    {
        $curl = new CurlHelperProbe();

        $headers = $curl->jsonHeaders([
            'content-type: application/vnd.api+json',
            'ACCEPT: application/json',
            'Content-Length: 12',
        ], 'hello');

        $this->assertSame([
            'content-type: application/vnd.api+json',
            'ACCEPT: application/json',
            'Content-Length: 12',
        ], $headers);
    }

    public function testJsonPayloadHeadersFillMissingDefaults(): void
    {
        $curl = new CurlHelperProbe();

        $headers = $curl->jsonHeaders(['X-Test: one'], 'hello');

        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains('Accept: application/json', $headers);
        $this->assertContains('Content-Length: 5', $headers);
    }
}
