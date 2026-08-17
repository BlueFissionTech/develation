<?php

namespace BlueFission\Tests\Data\Storage;

use BlueFission\Connections\Redis as RedisConnection;
use BlueFission\Data\Storage\Redis;
use BlueFission\Tests\Support\FakeRedisClient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    public function testStorageRoundTripsArbitraryValuesWithTtl(): void
    {
        $client = new FakeRedisClient();
        $connection = new RedisConnection(['prefix' => 'test:'], $client);
        $storage = new Redis(['key' => 'profile', 'ttl' => 30], $connection);
        $value = ['name' => 'Ada', 'roles' => ['admin']];

        $storage->activate();
        $storage->contents($value);
        $storage->write();
        $storage->contents(null);
        $storage->read();

        $this->assertSame($value, $storage->contents());
        $this->assertSame(30, $client->expirations['test:profile']);

        $storage->delete();
        $this->assertFalse($client->get('test:profile'));
    }

    public function testStorageRequiresAKey(): void
    {
        $storage = new Redis([], new RedisConnection([], new FakeRedisClient()));

        $this->expectException(InvalidArgumentException::class);
        $storage->activate();
    }
}
