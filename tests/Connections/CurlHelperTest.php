<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
use PHPUnit\Framework\TestCase;

class CurlHelperProbe extends Curl
{
    public function getTarget(string $target, array $data): string
    {
        return $this->getTargetWithQuery($target, $data);
    }

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
    public function testGetTargetPreservesPathAndAppendsQueryBeforeFragment(): void
    {
        $curl = new CurlHelperProbe();

        $this->assertSame('https://example.test/items', $curl->getTarget('https://example.test/items', []));
        $this->assertSame('https://example.test/items?existing=1&added=two',
            $curl->getTarget('https://example.test/items?existing=1', ['added' => 'two']));
        $this->assertSame('https://example.test/items?added=two#part',
            $curl->getTarget('https://example.test/items#part', ['added' => 'two']));
        $this->assertSame('https://example.test/items?existing=1#part',
            $curl->getTarget('https://example.test/items?existing=1#part', []));
    }

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
