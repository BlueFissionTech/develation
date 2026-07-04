<?php
namespace BlueFission\Tests\Net;

use BlueFission\Connections\Curl;
use BlueFission\Net\HTTPClient;
use PHPUnit\Framework\TestCase;

class HTTPClientTest extends TestCase
{
    public function testHeaderLinesNormalizeThroughStrAndParseThroughArr()
    {
        $client = new class($this->createMock(Curl::class)) extends HTTPClient {
            public function payload(string $body): mixed
            {
                return $this->requestBodyPayload($body);
            }

            public function normalized($headers): array
            {
                return $this->normalizeHeaderLines($headers);
            }

            public function parsed(array $lines): array
            {
                return $this->parseHeaderLines($lines);
            }
        };

        $lines = $client->normalized("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nX-Trace: abc:def\r\n\r\n");

        $this->assertSame([
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
            'X-Trace: abc:def',
        ], $lines);

        $this->assertSame([
            'Content-Type' => 'application/json',
            'X-Trace' => 'abc:def',
        ], $client->parsed($lines));
    }

    public function testRequestBodyPayloadUsesHttpJsonDecodeHelper()
    {
        $client = new class($this->createMock(Curl::class)) extends HTTPClient {
            public function payload(string $body): mixed
            {
                return $this->requestBodyPayload($body);
            }
        };

        $this->assertSame(['ok' => true], $client->payload('{"ok":true}'));
        $this->assertSame('not-json', $client->payload('not-json'));
    }
}
