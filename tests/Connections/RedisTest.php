<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
use BlueFission\Connections\Redis;
use BlueFission\Tests\Support\FakeRedisClient;
use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    public function testConnectionAppliesAuthenticationDatabaseAndPrefix(): void
    {
        $client = new FakeRedisClient();
        $connection = new Redis([
            'host' => 'redis.internal',
            'port' => 6380,
            'timeout' => 1.5,
            'username' => 'worker',
            'password' => 'secret',
            'database' => 4,
            'prefix' => 'app:',
        ], $client);

        $connection->open();

        $this->assertSame(Connection::STATUS_CONNECTED, $connection->status());
        $this->assertSame(['redis.internal', 6380, 1.5, null, 0, 2.5], $client->connectionArguments);
        $this->assertSame(['worker', 'secret'], $client->credentials);
        $this->assertSame(4, $client->database);
        $this->assertSame('app:key', $connection->key('key'));
    }

    public function testCommandAndQueryExposeClientOperations(): void
    {
        $client = new FakeRedisClient();
        $connection = new Redis([], $client);

        $this->assertTrue($connection->command('set', ['key', 'value']));
        $this->assertSame('value', $connection->command('get', ['key']));

        $connection->query(['get', ['key']]);
        $this->assertSame('value', $connection->result());

        $connection->close();
        $this->assertTrue($client->closed);
    }
}
