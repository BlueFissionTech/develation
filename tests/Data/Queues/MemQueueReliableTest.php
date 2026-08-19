<?php

namespace BlueFission\Tests\Data\Queues;

use BlueFission\Data\Queues\MemQueue;
use BlueFission\Data\Queues\Queue;
use BlueFission\Data\Queues\QueueReceipt;
use BlueFission\Tests\Support\FakeMemcachedClient;
use PHPUnit\Framework\TestCase;

class MemQueueReliableTest extends TestCase
{
    protected function setUp(): void
    {
        MemQueue::reset();
        MemQueue::setClient(new FakeMemcachedClient());
        MemQueue::configure(['lease_seconds' => 30, 'max_attempts' => 2]);
        MemQueue::setMode(Queue::FIFO);
    }

    protected function tearDown(): void
    {
        MemQueue::purge('jobs');
        MemQueue::reset();
    }

    public function testSimpleQueueApiSupportsFifoAndFilo(): void
    {
        MemQueue::enqueue('jobs', 'first');
        MemQueue::enqueue('jobs', 'second');

        $this->assertSame('first', MemQueue::dequeue('jobs'));

        MemQueue::setMode(Queue::FILO);
        MemQueue::enqueue('jobs', 'third');

        $this->assertSame('third', MemQueue::dequeue('jobs'));
        $this->assertSame('second', MemQueue::dequeue('jobs'));
        $this->assertTrue(MemQueue::isEmpty('jobs'));
    }

    public function testClaimCanBeAcknowledgedWithoutDestructiveRead(): void
    {
        MemQueue::enqueue('jobs', ['task' => 'send']);
        $receipt = MemQueue::claim('jobs', 30, 100);

        $this->assertInstanceOf(QueueReceipt::class, $receipt);
        $this->assertSame(['task' => 'send'], $receipt->payload);
        $this->assertSame(1, $receipt->attempts);
        $this->assertSame(130, $receipt->leaseExpiresAt);
        $this->assertTrue(MemQueue::isEmpty('jobs'));
        $this->assertTrue(MemQueue::acknowledge('jobs', $receipt));
        $this->assertFalse(MemQueue::acknowledge('jobs', $receipt));
    }

    public function testClaimedPayloadIsUnavailableToAnotherWorker(): void
    {
        MemQueue::enqueue('jobs', 'reserved');
        $receipt = MemQueue::claim('jobs', 30, 100);

        $this->assertInstanceOf(QueueReceipt::class, $receipt);
        $this->assertNull(MemQueue::claim('jobs', 30, 101));
        $this->assertTrue(MemQueue::acknowledge('jobs', $receipt));
    }

    public function testReleaseReturnsClaimToReadyQueue(): void
    {
        MemQueue::enqueue('jobs', 'retry');
        $receipt = MemQueue::claim('jobs', 30, 100);

        $this->assertTrue(MemQueue::release('jobs', $receipt));

        $retried = MemQueue::claim('jobs', 30, 101);
        $this->assertSame('retry', $retried->payload);
        $this->assertSame(2, $retried->attempts);
    }

    public function testReceiptTokenProtectsWorkerOwnership(): void
    {
        MemQueue::enqueue('jobs', 'protected');
        $receipt = MemQueue::claim('jobs', 30, 100);
        $forged = new QueueReceipt(
            $receipt->id,
            $receipt->payload,
            $receipt->attempts,
            $receipt->leaseExpiresAt,
            'another-worker-token',
        );

        $this->assertFalse(MemQueue::acknowledge('jobs', $forged));
        $this->assertFalse(MemQueue::release('jobs', $forged));
        $this->assertTrue(MemQueue::acknowledge('jobs', $receipt));
    }

    public function testExpiredLeaseIsRecovered(): void
    {
        MemQueue::enqueue('jobs', 'recover');
        MemQueue::claim('jobs', 5, 100);

        $this->assertSame(0, MemQueue::recover('jobs', 104));
        $this->assertSame(1, MemQueue::recover('jobs', 105));

        $receipt = MemQueue::claim('jobs', 5, 106);
        $this->assertSame('recover', $receipt->payload);
        $this->assertSame(2, $receipt->attempts);
    }

    public function testRetryExhaustionMovesPayloadToFailedQueue(): void
    {
        MemQueue::enqueue('jobs', ['task' => 'fail']);
        $first = MemQueue::claim('jobs', 5, 100);
        MemQueue::release('jobs', $first);
        $second = MemQueue::claim('jobs', 5, 101);

        $this->assertTrue(MemQueue::release('jobs', $second));
        $this->assertTrue(MemQueue::isEmpty('jobs'));

        $failed = MemQueue::failed('jobs');
        $this->assertCount(1, $failed);
        $this->assertSame(['task' => 'fail'], $failed->first()['payload']);
        $this->assertSame(2, $failed->first()['attempts']);

        $this->assertTrue(MemQueue::retryFailed('jobs', $failed->first()['id']));
        $retried = MemQueue::claim('jobs', 5, 107);
        $this->assertSame(1, $retried->attempts);
        $this->assertTrue(MemQueue::acknowledge('jobs', $retried));
    }

    public function testFailedPayloadCanBeDiscarded(): void
    {
        MemQueue::configure(['max_attempts' => 1]);
        MemQueue::enqueue('jobs', 'discard');
        $receipt = MemQueue::claim('jobs', 5, 100);
        MemQueue::release('jobs', $receipt);
        $failed = MemQueue::failed('jobs')->first();

        $this->assertTrue(MemQueue::discardFailed('jobs', $failed['id']));
        $this->assertFalse(MemQueue::discardFailed('jobs', $failed['id']));
        $this->assertCount(0, MemQueue::failed('jobs'));
    }
}
