<?php

namespace BlueFission\Net;

use BlueFission\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private $_statusCode;
    private $_headers;
    private $_body;
    private $_protocolVersion;
    private $_reasonPhrase;

    public function __construct($statusCode = 200, $headers = [], $body = null, $protocolVersion = '1.1', $reasonPhrase = '')
    {
        $this->_statusCode = $statusCode;
        $this->_headers = Arr::make($headers);
        $this->_body = $body;
        $this->_protocolVersion = $protocolVersion;
        $this->_reasonPhrase = $reasonPhrase;
    }

    public function __clone()
    {
        $this->_headers = Arr::make($this->_headers->val());
    }

    public function getStatusCode(): int
    {
        return $this->_statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->_statusCode = $code;
        $new->_reasonPhrase = $reasonPhrase;
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->_reasonPhrase;
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
        if (!$this->hasHeader($name)) {
            return [];
        }
        return $this->_headers[$name];
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
