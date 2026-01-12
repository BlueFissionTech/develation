<?php

namespace BlueFission\Net;

use BlueFission\Connections\Curl;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HTTPClient implements ClientInterface
{
    protected $_curl;

    public function __construct(Curl $curl)
    {
        $this->_curl = $curl;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->_curl->config([
            'target' => (string)$request->getUri(),
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
        ]);

        // Set the request body if there is one
        $body = (string) $request->getBody();
        if (!empty($body)) {
            $this->_curl->assign(json_decode($body, true) ?: $body);
        }

        $this->_curl
            ->open()
            ->query()
            ->close();

        return new Response(
            $this->getStatusCode(),
            $this->getHeaders(),
            $this->_curl->result()
        );
    }

    protected function getStatusCode(): int
    {
        return curl_getinfo($this->_curl->connection(), CURLINFO_HTTP_CODE);
    }

    protected function getHeaders(): array
    {
        $headerSize = curl_getinfo($this->_curl->connection(), CURLINFO_HEADER_SIZE);
        $headerString = Str::sub($this->_curl->result(), 0, $headerSize);
        $lines = $this->normalizeHeaderLines($headerString);
        $headers = [];
        foreach ($lines as $line) {
            if (Str::pos($line, ':') !== false) {
                list($key, $value) = explode(':', Str::use(), 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }

    protected function normalizeHeaderLines($headerString): array
    {
        if (Val::isEmpty($headerString)) {
            return [];
        }

        $headerString = Val::grab();

        if (Str::is($headerString)) {
            $headerString = Str::replace(Str::grab(), "\r", '');
            $lines = Str::split($headerString, "\n");
        } elseif (Arr::is($headerString)) {
            $lines = Arr::grab();
        } else {
            return [];
        }

        if (!Arr::isNotEmpty($lines)) {
            return [];
        }

        return Arr::grab();
    }
}
