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
        if (Val::isNotEmpty($body)) {
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
        return $this->parseHeaderLines($lines);
    }

    protected function parseHeaderLines(array $lines): array
    {
        $headers = Arr::make();
        foreach ($lines as $line) {
            $separator = Str::pos($line, ':');
            if ($separator !== false) {
                $key = Str::sub($line, 0, $separator);
                $value = Str::sub($line, $separator + 1);
                $headers[Str::trim($key)] = Str::trim($value);
            }
        }

        return $headers->val();
    }

    protected function normalizeHeaderLines($headerString): array
    {
        if (Val::isEmpty($headerString)) {
            return [];
        }

        if (Str::is($headerString)) {
            $lines = Str::make($headerString)
                ->replace("\r", '')
                ->split("\n")
                ->filter(fn ($line) => Str::isNotEmpty($line))
                ->values()
                ->val();
        } elseif (Arr::is($headerString)) {
            $lines = Arr::grab();
        } else {
            return [];
        }

        if (Arr::isEmpty($lines)) {
            return [];
        }

        return $lines;
    }
}
