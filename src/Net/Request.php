<?php

namespace BlueFission\Net;

use BlueFission\Arr;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    private $_method;
    private $_uri;
    private $_headers = [];
    private $_body;
    private $_protocolVersion;

    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = [],
        StreamInterface $body = null,
        string $protocolVersion = '1.1'
    ) {
        $this->_method = $method;
        $this->_uri = $uri;
        $this->_headers = Arr::make($headers);
        $this->_body = $body;
        $this->_protocolVersion = $protocolVersion;
    }

    public function __clone()
    {
        $this->_headers = Arr::make($this->_headers->val());
    }

    public function getRequestTarget(): string
    {
        return $this->_uri->getPath();
    }

    public function withRequestTarget($requestTarget): self
    {
        $new = clone $this;
        $new->_uri = $new->_uri->withPath($requestTarget);
        return $new;
    }

    public function getMethod(): string
    {
        return $this->_method;
    }

    public function withMethod($method): self
    {
        $new = clone $this;
        $new->_method = $method;
        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->_uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new = clone $this;
        $new->_uri = $uri;
        if (!$preserveHost) {
            $new->_headers['Host'] = [$uri->getHost()];
        }
        return $new;
    }

    public function getProtocolVersion(): string
    {
        return $this->_protocolVersion;
    }

    public function withProtocolVersion($version): self
    {
        $new = clone $this;
        $new->_protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->_headers->val();
    }

    public function hasHeader($name): bool
    {
        return $this->_headers->hasKey($name);
    }

    public function getHeader($name): array
    {
        return $this->_headers[$name] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return Arr::make($this->getHeader($name))->join(', ')->val();
    }

    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->_headers[$name] = Arr::toArray($value);
        return $new;
    }

    public function withAddedHeader($name, $value): self
    {
        $new = clone $this;
        if ($new->hasHeader($name)) {
            $new->_headers[$name] = Arr::make($new->_headers[$name])
                ->mergeRecursive(Arr::toArray($value))
                ->val();
        } else {
            $new->_headers[$name] = Arr::toArray($value);
        }
        return $new;
    }

    public function withoutHeader($name): self
    {
        $new = clone $this;
        unset($new->_headers[$name]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->_body;
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->_body = $body;
        return $new;
    }
}
