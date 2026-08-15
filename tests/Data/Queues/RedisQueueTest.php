<?php

namespace BlueFission\Tests\Data\Queues;

use BlueFission\Connections\Redis as RedisConnection;
use BlueFission\Data\Queues\Queue;
use BlueFission\Data\Queues\QueueReceipt;
use BlueFission\Data\Queues\RedisQueue;
use BlueFission\Tests\Support\FakeRedisClient;
use PHPUnit\Framework\TestCase;

class RedisQueueTest extends TestCase
{
    private FakeRedisClient $client;

    protected function setUp(): void
    {
        $this->client = new FakeRedisClient();
        RedisQueue::reset();
        RedisQueue::setConnection(new RedisConnection(['prefix' => 'test:'], $this->client));
        RedisQueue::configure(['queue_prefix' => 'queues:', 'max_attempts' => 2]);
        RedisQueue::setMode(Queue::FIFO);
    }

    protected function tearDown(): void
    {
        RedisQueue::purge('jobs');
        RedisQueue::reset();
    }

    public function testSimpleQueueApiSupportsFifoAndFilo(): void
    {
        RedisQueue::enqueue('jobs', 'first');
        RedisQueue::enqueue('jobs', 'second');

        $this->assertFalse(RedisQueue::isEmpty('jobs'));
        $this->assertSame('first', RedisQueue::dequeue('jobs'));

        RedisQueue::setMode(Queue::FILO);
        RedisQueue::enqueue('jobs', 'third');

        $this->assertSame('third', RedisQueue::dequeue('jobs'));
        $this->assertSame('second', RedisQueue::dequeue('jobs'));
        $this->assertTrue(RedisQueue::isEmpty('jobs'));
    }

    public function testClaimCanBeAcknowledgedWithoutDestructiveRead(): void
    {
        RedisQueue::configure(['lease_seconds' => 30]);
        RedisQueue::enqueue('jobs', ['task' => 'send']);
        $now = time();

        $receipt = RedisQueue::claim('jobs', 0, $now);

        $this->assertInstanceOf(QueueReceipt::class, $receipt);
        $this->assertSame(['task' => 'send'], $receipt->payload);
        $this->assertSame(1, $receipt->attempts);
        $this->assertSame($now + 30, $receipt->leaseExpiresAt);
        $this->assertTrue(RedisQueue::isEmpty('jobs'));
        $this->assertTrue(RedisQueue::acknowledge('jobs', $receipt));
        $this->assertFalse(RedisQueue::acknowledge('jobs', $receipt));
    }

    public function testReleaseReturnsClaimToTheReadyQueue(): void
    {
        RedisQueue::enqueue('jobs', 'retry');
        $receipt = RedisQueue::claim('jobs', 30, 100);

        $this->assertTrue(RedisQueue::release('jobs', $receipt));

        $retried = RedisQueue::claim('jobs', 30, 101);
        $this->assertSame('retry', $retried->payload);
        $this->assertSame(2, $retried->attempts);
    }

    public function testReceiptTokenPreventsAnotherWorkerFromCompletingAClaim(): void
    {
        RedisQueue::enqueue('jobs', 'protected');
        $receipt = RedisQueue::claim('jobs', 30, time());
        $forged = new QueueReceipt(
            $receipt->id,
            $receipt->payload,
            $receipt->attempts,
            $receipt->leaseExpiresAt,
            'another-worker-token',
        );

        $this->assertFalse(RedisQueue::acknowledge('jobs', $forged));
        $this->assertFalse(RedisQueue::release('jobs', $forged));
        $this->assertTrue(RedisQueue::acknowledge('jobs', $receipt));
    }

    public function testExpiredLeaseIsRecovered(): void
    {
        RedisQueue::enqueue('jobs', 'recover');
        RedisQueue::claim('jobs', 5, 100);

        $this->assertSame(0, RedisQueue::recover('jobs', 104));
        $this->assertSame(1, RedisQueue::recover('jobs', 105));

        $receipt = RedisQueue::claim('jobs', 5, 106);
        $this->assertSame('recover', $receipt->payload);
        $this->assertSame(2, $receipt->attempts);
    }

    public function testRetryExhaustionMovesPayloadToFailedQueue(): void
    {
        RedisQueue::enqueue('jobs', ['task' => 'fail']);
        $first = RedisQueue::claim('jobs', 5, 100);
        RedisQueue::release('jobs', $first);
        $second = RedisQueue::claim('jobs', 5, 101);

        $this->assertTrue(RedisQueue::release('jobs', $second));
        $this->assertTrue(RedisQueue::isEmpty('jobs'));

        $failed = RedisQueue::failed('jobs');
        $this->assertCount(1, $failed);
        $this->assertSame(['task' => 'fail'], $failed->first()['payload']);
        $this->assertSame(2, $failed->first()['attempts']);

        $this->assertTrue(RedisQueue::retryFailed('jobs', $failed->first()['id']));
        $retried = RedisQueue::claim('jobs', 5, 107);
        $this->assertSame(1, $retried->attempts);
        $this->assertTrue(RedisQueue::acknowledge('jobs', $retried));
    }

    public function testFailedPayloadCanBeDiscarded(): void
    {
        RedisQueue::configure(['max_attempts' => 1]);
        RedisQueue::enqueue('jobs', 'discard');
        $receipt = RedisQueue::claim('jobs', 5, 100);
        RedisQueue::release('jobs', $receipt);
        $failed = RedisQueue::failed('jobs')->first();

        $this->assertTrue(RedisQueue::discardFailed('jobs', $failed['id']));
        $this->assertFalse(RedisQueue::discardFailed('jobs', $failed['id']));
        $this->assertCount(0, RedisQueue::failed('jobs'));
    }
}
