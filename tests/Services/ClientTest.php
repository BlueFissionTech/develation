<?php

namespace BlueFission\Tests\Services;

use BlueFission\Connections\Curl;
use BlueFission\IObj;
use BlueFission\Services\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testGetBuildsNormalizedTargetAndReturnsResult()
    {
        $curl = new ClientTestCurl('payload');
        $client = new ClientTestDouble($curl, 'https://api.example.test/root/');

        $response = $client->get('/users');

        $this->assertSame('payload', $response);
        $this->assertSame('https://api.example.test/root/users', $curl->config('target'));
        $this->assertSame([null], $curl->queries);
        $this->assertTrue($curl->opened);
        $this->assertTrue($curl->closed);
    }

    public function testPostUsesHttpQueryHelper()
    {
        $curl = new ClientTestCurl('created');
        $client = new ClientTestDouble($curl, 'https://api.example.test/root/');

        $response = $client->post(['name' => 'Jane Doe'], '/users/');

        $this->assertSame('created', $response);
        $this->assertSame('https://api.example.test/root/users', $curl->config('target'));
        $this->assertSame(['name=Jane+Doe'], $curl->queries);
    }
}

class ClientTestDouble extends Client
{
    public function __construct(ClientTestCurl $curl, string $baseUrl)
    {
        parent::__construct();

        $this->_curl = $curl;
        $this->_baseUrl = $baseUrl;
    }
}

class ClientTestCurl extends Curl
{
    public array $queries = [];
    public bool $opened = false;
    public bool $closed = false;

    private array $config = [];
    private mixed $fakeResult;

    public function __construct(mixed $result)
    {
        $this->fakeResult = $result;
    }

    public function config($config = null, $value = null): mixed
    {
        if (is_array($config)) {
            foreach ($config as $key => $item) {
                $this->config[$key] = $item;
            }

            return $this;
        }

        if ($value !== null) {
            $this->config[$config] = $value;

            return $this;
        }

        if ($config !== null) {
            return $this->config[$config] ?? null;
        }

        return $this->config;
    }

    public function open(): IObj
    {
        $this->opened = true;

        return $this;
    }

    public function query($query = null): IObj
    {
        $this->queries[] = $query;

        return $this;
    }

    public function close(): IObj
    {
        $this->closed = true;

        return $this;
    }

    public function result()
    {
        return $this->fakeResult;
    }
}
