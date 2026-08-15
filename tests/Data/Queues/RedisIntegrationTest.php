<?php

namespace BlueFission\Tests\Data\Queues;

use BlueFission\Connections\Redis as RedisConnection;
use BlueFission\Data\Queues\Queue;
use BlueFission\Data\Queues\RedisQueue;
use BlueFission\Data\Storage\Redis as RedisStorage;
use BlueFission\Tests\Support\TestEnvironment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/TestEnvironment.php';

class RedisIntegrationTest extends TestCase
{
    private ?RedisConnection $connection = null;
    private string $queue = '';
    private string $storageKey = '';

    protected function setUp(): void
    {
        $config = TestEnvironment::redisConfig();
        if (!class_exists('\Redis') || $config === null) {
            $this->markTestSkipped('Redis tests require ext-redis and DEV_ELATION_REDIS_HOST.');
        }

        $this->connection = new RedisConnection($config);
        $this->connection->open();
        if ($this->connection->connection() === null) {
            $this->markTestSkipped('The configured Redis service is unavailable.');
        }

        $suffix = bin2hex(random_bytes(6));
        $this->queue = "integration:{$suffix}";
        $this->storageKey = "storage:{$suffix}";

        RedisQueue::reset();
        RedisQueue::setConnection($this->connection);
        RedisQueue::configure(['max_attempts' => 2]);
        RedisQueue::setMode(Queue::FIFO);
    }

    protected function tearDown(): void
    {
        if ($this->connection === null) {
            return;
        }

        RedisQueue::purge($this->queue);
        $this->connection->command('del', [$this->connection->key($this->storageKey)]);
        $this->connection->close();
        RedisQueue::reset();
    }

    public function testStorageAndReliableQueueAgainstRedis(): void
    {
        $storage = new RedisStorage([
            'key' => $this->storageKey,
            'ttl' => 30,
        ], $this->connection);
        $storage->activate();
        $storage->contents(['status' => 'ready']);
        $storage->write()->read();

        $this->assertSame(['status' => 'ready'], $storage->contents());

        RedisQueue::enqueue($this->queue, ['job' => 1]);
        $receipt = RedisQueue::claim($this->queue, 5);

        $this->assertSame(['job' => 1], $receipt->payload);
        $this->assertTrue(RedisQueue::release($this->queue, $receipt));

        $retried = RedisQueue::claim($this->queue, 5);
        $this->assertSame(2, $retried->attempts);
        $this->assertTrue(RedisQueue::acknowledge($this->queue, $retried));
    }
}
