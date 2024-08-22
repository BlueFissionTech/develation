<?php

namespace BlueFission\Net;

use BlueFission\Connections\Curl;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HTTPClient implements ClientInterface
{
    protected $curl;

    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->curl->config([
            'target' => (string)$request->getUri(),
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
        ]);

        // Set the request body if there is one
        $body = (string) $request->getBody();
        if (!empty($body)) {
            $this->curl->assign(json_decode($body, true) ?: $body);
        }

        $this->curl
            ->open()
            ->query()
            ->close();

        return new Response(
            $this->getStatusCode(),
            $this->getHeaders(),
            $this->curl->result()
        );
    }

    protected function getStatusCode(): int
    {
        return curl_getinfo($this->curl->connection(), CURLINFO_HTTP_CODE);
    }

    protected function getHeaders(): array
    {
        $headerSize = curl_getinfo($this->curl->connection(), CURLINFO_HEADER_SIZE);
        $headerString = Str::sub($this->curl->result(), 0, $headerSize);
        $headers = [];
        foreach (explode("\r\n", $headerString) as $line) {
            if (Str::pos($line, ':') !== false) {
                list($key, $value) = explode(':', Str::use(), 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }
}
