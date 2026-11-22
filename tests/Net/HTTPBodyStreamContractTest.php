<?php

namespace BlueFission\Tests\Net;

use BlueFission\Connections\Curl;
use BlueFission\Net\HTTPClient;
use BlueFission\Net\Request;
use BlueFission\Net\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class HTTPBodyStreamContractTest extends TestCase
{
    public function testRequestWithoutBodyCurrentlyViolatesPsrStreamReturn(): void
    {
        $request = new Request('GET', $this->createMock(UriInterface::class));

        $this->expectException(\TypeError::class);
        $request->getBody();
    }

    public function testResponseWithRawTransportBytesCurrentlyViolatesPsrStreamReturn(): void
    {
        $response = new Response(200, [], "body\0bytes");

        $this->expectException(\TypeError::class);
        $response->getBody();
    }

    public function testExplicitStreamsRemainTheSameObjects(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $request = new Request('POST', $this->createMock(UriInterface::class), [], $stream);
        $response = new Response(200, [], $stream);

        $this->assertSame($stream, $request->getBody());
        $this->assertSame($stream, $response->getBody());
    }

    public function testJsonRequestBodyCurrentlyLosesOriginalBytes(): void
    {
        $client = new class($this->createMock(Curl::class)) extends HTTPClient {
            public function payload(string $body): mixed
            {
                return $this->requestBodyPayload($body);
            }
        };
        $raw = " {\"a\" : 1}\n";

        $this->assertSame(['a' => 1], $client->payload($raw));
        $this->assertNotSame($raw, $client->payload($raw));
    }
}
