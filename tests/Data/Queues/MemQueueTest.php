<?php

namespace BlueFission\Tests\Data\Queues;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\MemQueue;

class MemQueueTest extends TestCase
{
    private static $testQueueName = 'testQueue';

    protected function setUp(): void
    {
        MemQueue::setMode(MemQueue::FIFO); // Default to FIFO for consistency in testing
    }

    protected function tearDown(): void
    {
        // Clean up the queue keys in Memcached after each test to prevent residue data affecting other tests
        while (!MemQueue::isEmpty(self::$testQueueName)) {
            MemQueue::dequeue(self::$testQueueName);
        }
    }

    public function testQueueIsEmptyInitially()
    {
        $this->assertTrue(MemQueue::isEmpty(self::$testQueueName), "Queue should be empty initially.");
    }

    public function testEnqueueAndDequeueItems()
    {
        MemQueue::enqueue(self::$testQueueName, 'firstItem');
        MemQueue::enqueue(self::$testQueueName, 'secondItem');

        $this->assertFalse(MemQueue::isEmpty(self::$testQueueName), "Queue should not be empty after adding items.");

        $firstDequeued = MemQueue::dequeue(self::$testQueueName);
        $secondDequeued = MemQueue::dequeue(self::$testQueueName);

        $this->assertEquals('firstItem', $firstDequeued, "The first dequeued item should be 'firstItem'.");
        $this->assertEquals('secondItem', $secondDequeued, "The second dequeued item should be 'secondItem'.");
    }

    public function testQueueSupportsFILOMode()
    {
        MemQueue::setMode(MemQueue::FILO);
        MemQueue::enqueue(self::$testQueueName, 'firstItem');
        MemQueue::enqueue(self::$testQueueName, 'secondItem');

        $dequeued = MemQueue::dequeue(self::$testQueueName);
        $this->assertEquals('secondItem', $dequeued, "The dequeued item should be 'secondItem' when in FILO mode.");
    }

    public function testDequeueWithLimits()
    {
        MemQueue::enqueue(self::$testQueueName, 'firstItem');
        MemQueue::enqueue(self::$testQueueName, 'secondItem');
        MemQueue::enqueue(self::$testQueueName, 'thirdItem');

        $limitedItems = MemQueue::dequeue(self::$testQueueName, 1, 2);

        $this->assertContains('secondItem', $limitedItems, "Dequeue should include 'secondItem'.");
        $this->assertCount(1, $limitedItems, "Only one item should be dequeued.");
    }

    public function testQueueIsEmptyAfterClearing()
    {
        MemQueue::enqueue(self::$testQueueName, 'item');
        MemQueue::dequeue(self::$testQueueName);
        $this->assertTrue(MemQueue::isEmpty(self::$testQueueName), "Queue should be empty after removing all items.");
    }
}
