<?php

namespace BlueFission\Net;

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
        $this->_headers = $headers;
        $this->_body = $body;
        $this->_protocolVersion = $protocolVersion;
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
        return $this->_headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->_headers[$name]);
    }

    public function getHeader($name): array
    {
        return $this->_headers[$name] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->_headers[$name] = (array)$value;
        return $new;
    }

    public function withAddedHeader($name, $value): self
    {
        $new = clone $this;
        if ($new->hasHeader($name)) {
            $new->_headers[$name] = array_merge($new->_headers[$name], (array)$value);
        } else {
            $new->_headers[$name] = (array)$value;
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
