<?php

namespace BlueFission\Services;

use BlueFission\Arr;
use BlueFission\Connections\Curl;
use BlueFission\Net\HTTP;
use BlueFission\Str;

// @TODO: make other classes extend this base class
abstract class Client extends Service
{
    protected ?Curl $_curl;
    protected ?string $_baseUrl;
    protected ?string $_apiKey;
    protected $_client;

    public function __construct()
    {
        parent::__construct();

        $this->_curl = new Curl([
            'method' => 'post',
        ]);
    }

    public function get(string $endpoint = '')
    {
        $target = $this->target($endpoint);

        $this->_curl->config('target', $target);
        $this->_curl->open();
        $this->_curl->query();
        $response = $this->_curl->result();
        $this->_curl->close();

        return $response;
    }

    public function post($data, string $endpoint = '')
    {
        $target = $this->target($endpoint);

        $this->_curl->config('target', $target);
        $this->_curl->open();
        $this->_curl->query(HTTP::query(Arr::toArray($data)));
        $response = $this->_curl->result();
        $this->_curl->close();

        return $response;
    }

    protected function target(string $endpoint = ''): string
    {
        return Arr::make([$this->_baseUrl ?? '', $endpoint])
            ->map(fn ($part) => Str::make($part)->trim()->trim('/')->val())
            ->filter(fn ($part) => Str::make($part)->isNotEmpty())
            ->join('/')
            ->val();
    }
}
