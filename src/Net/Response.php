<?php

namespace BlueFission\Net;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private $statusCode;
    private $headers;
    private $body;
    private $protocolVersion;

    public function __construct($statusCode = 200, $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->protocolVersion = $protocolVersion;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    // Implement other methods from ResponseInterface...
}
