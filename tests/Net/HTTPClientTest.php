<?php
namespace BlueFission\Tests\Net;

use BlueFission\Connections\Curl;
use BlueFission\IObj;
use BlueFission\Net\HTTPClient;
use BlueFission\Net\HTTPClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class HTTPClientTest extends TestCase
{
    public function testExplicitFactoryConstructsIndependentClientsWithoutNetwork(): void
    {
        $first = HTTPClientFactory::create();
        $second = HTTPClientFactory::create();

        $this->assertInstanceOf(HTTPClient::class, $first);
        $this->assertInstanceOf(HTTPClient::class, $second);
        $this->assertNotSame($first, $second);
    }

    public function testResponseMetadataIsCapturedBeforeOwnedCurlCloses(): void
    {
        $curl = $this->offlineCurl();
        $client = new class($curl) extends HTTPClient {
            protected function getStatusCode(): int
            {
                if ($this->_curl->closed) {
                    throw new \LogicException('Status was read after closing the connection.');
                }
                return 207;
            }

            protected function getHeaders(): array
            {
                if ($this->_curl->closed) {
                    throw new \LogicException('Headers were read after closing the connection.');
                }
                return ['X-Result' => ['ready']];
            }
        };

        $response = $client->sendRequest($this->offlineRequest());

        $this->assertSame(207, $response->getStatusCode());
        $this->assertSame(['X-Result' => ['ready']], $response->getHeaders());
        $this->assertTrue($curl->closed);
    }

    public function testThrownQueryFailureClosesOwnedCurlWithoutMaskingFailure(): void
    {
        $curl = $this->offlineCurl();
        $curl->failQuery = true;
        $curl->failClose = true;
        $client = new HTTPClient($curl);

        try {
            $client->sendRequest($this->offlineRequest());
            $this->fail('The query failure must remain visible.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('offline query failure', $exception->getMessage());
        }

        $this->assertTrue($curl->closed);
    }

    private function offlineCurl(): Curl
    {
        return new class extends Curl {
            public bool $closed = false;
            public bool $failQuery = false;
            public bool $failClose = false;

            public function open(): IObj
            {
                $this->closed = false;
                return $this;
            }

            public function query($query = null): IObj
            {
                if ($this->failQuery) {
                    throw new \RuntimeException('offline query failure');
                }
                $this->_result = 'body';
                return $this;
            }

            public function close(): IObj
            {
                $this->closed = true;
                if ($this->failClose) {
                    throw new \RuntimeException('offline close failure');
                }
                return $this;
            }
        };
    }

    private function offlineRequest(): RequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn('https://example.test/resource');
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('');
        $request = $this->createMock(RequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaders')->willReturn([]);
        $request->method('getBody')->willReturn($stream);
        return $request;
    }

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
