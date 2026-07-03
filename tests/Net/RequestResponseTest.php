<?php
namespace BlueFission\Tests\Net;

use BlueFission\Arr;
use BlueFission\Net\Request;
use BlueFission\Net\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class RequestResponseTest extends TestCase
{
    public function testRequestHeadersUseArrStorageAndPsrArrays()
    {
        $request = new Request('GET', $this->uri('/start'), [
            'Accept' => ['text/plain'],
        ]);

        $this->assertInstanceOf(Arr::class, $this->objectProperty($request, '_headers'));
        $this->assertSame(['Accept' => ['text/plain']], $request->getHeaders());
        $this->assertTrue($request->hasHeader('Accept'));
        $this->assertSame('text/plain', $request->getHeaderLine('Accept'));

        $withHeader = $request->withAddedHeader('Accept', ['text/html', 'text/plain']);

        $this->assertSame(['text/plain'], $request->getHeader('Accept'));
        $this->assertSame(['text/plain', 'text/html', 'text/plain'], $withHeader->getHeader('Accept'));
        $this->assertSame('text/plain, text/html, text/plain', $withHeader->getHeaderLine('Accept'));
    }

    public function testRequestWithUriSetsHostOnArrHeaders()
    {
        $request = new Request('GET', $this->uri('/start'));
        $updated = $request->withUri($this->uri('/next', 'example.org'));

        $this->assertSame(['example.org'], $updated->getHeader('Host'));
        $this->assertSame([], $request->getHeader('Host'));
    }

    public function testResponseHeadersUseArrStorageAndPsrArrays()
    {
        $response = new Response(200, [
            'Cache-Control' => ['no-cache'],
        ]);

        $this->assertInstanceOf(Arr::class, $this->objectProperty($response, '_headers'));
        $this->assertSame(['Cache-Control' => ['no-cache']], $response->getHeaders());
        $this->assertTrue($response->hasHeader('Cache-Control'));
        $this->assertSame('no-cache', $response->getHeaderLine('Cache-Control'));

        $withHeader = $response->withAddedHeader('Cache-Control', ['private', 'no-cache']);

        $this->assertSame(['no-cache'], $response->getHeader('Cache-Control'));
        $this->assertSame(['no-cache', 'private', 'no-cache'], $withHeader->getHeader('Cache-Control'));
        $this->assertSame('no-cache, private, no-cache', $withHeader->getHeaderLine('Cache-Control'));
    }

    private function uri(string $path, string $host = 'example.com'): UriInterface
    {
        return new class($path, $host) implements UriInterface {
            private string $path;
            private string $host;

            public function __construct(string $path, string $host)
            {
                $this->path = $path;
                $this->host = $host;
            }

            public function getScheme(): string { return 'https'; }
            public function getAuthority(): string { return $this->host; }
            public function getUserInfo(): string { return ''; }
            public function getHost(): string { return $this->host; }
            public function getPort(): ?int { return null; }
            public function getPath(): string { return $this->path; }
            public function getQuery(): string { return ''; }
            public function getFragment(): string { return ''; }
            public function withScheme(string $scheme): UriInterface { return $this; }
            public function withUserInfo(string $user, ?string $password = null): UriInterface { return $this; }
            public function withHost(string $host): UriInterface { $new = clone $this; $new->host = $host; return $new; }
            public function withPort(?int $port): UriInterface { return $this; }
            public function withPath(string $path): UriInterface { $new = clone $this; $new->path = $path; return $new; }
            public function withQuery(string $query): UriInterface { return $this; }
            public function withFragment(string $fragment): UriInterface { return $this; }
            public function __toString(): string { return 'https://' . $this->host . $this->path; }
        };
    }

    private function objectProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
