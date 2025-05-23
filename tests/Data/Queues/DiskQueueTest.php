<?php

namespace BlueFission\Tests\Data\Queues;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\DiskQueue;

class DiskQueueTest extends TestCase
{
    private static $testQueueName = 'testQueue';

    protected function setUp(): void
    {
        DiskQueue::setMode(DiskQueue::FIFO); // Default to FIFO for consistency in testing
    }

    protected function tearDown(): void
    {
        // Clean up the queue directory after each test to prevent residue data affecting other tests
        if (!DiskQueue::isEmpty(self::$testQueueName)) {
            while (!DiskQueue::isEmpty(self::$testQueueName)) {
                DiskQueue::dequeue(self::$testQueueName);
            }
        }
    }

    public function testQueueIsEmptyInitially()
    {
        $this->assertTrue(DiskQueue::isEmpty(self::$testQueueName), "Queue should be empty initially.");
    }

    public function testEnqueueAndDequeueItems()
    {
        DiskQueue::enqueue(self::$testQueueName, 'firstItem');
        DiskQueue::enqueue(self::$testQueueName, 'secondItem');

        $this->assertFalse(DiskQueue::isEmpty(self::$testQueueName), "Queue should not be empty after adding items.");

        $firstDequeued = DiskQueue::dequeue(self::$testQueueName);
        $secondDequeued = DiskQueue::dequeue(self::$testQueueName);

        $this->assertEquals('firstItem', $firstDequeued, "The first dequeued item should be 'firstItem'.");
        $this->assertEquals('secondItem', $secondDequeued, "The second dequeued item should be 'secondItem'.");
    }

    public function testQueueSupportsFILOMode()
    {
        DiskQueue::setMode(DiskQueue::FILO);
        DiskQueue::enqueue(self::$testQueueName, 'firstItem');
        DiskQueue::enqueue(self::$testQueueName, 'secondItem');
        $dequeued = DiskQueue::dequeue(self::$testQueueName);
        $this->assertEquals('secondItem', $dequeued, "The dequeued item should be 'secondItem' when in FILO mode.");
    }

    public function testDequeueWithLimits()
    {
        DiskQueue::enqueue(self::$testQueueName, 'firstItem');
        DiskQueue::enqueue(self::$testQueueName, 'secondItem');
        DiskQueue::enqueue(self::$testQueueName, 'thirdItem');

        $limitedItems = DiskQueue::dequeue(self::$testQueueName, 1, 2);

        $this->assertContains('secondItem', $limitedItems, "Dequeue should include 'secondItem'.");
        $this->assertCount(1, $limitedItems, "Only one item should be dequeued.");
    }

    public function testQueueIsEmptyAfterClearing()
    {
        DiskQueue::enqueue(self::$testQueueName, 'item');
        DiskQueue::dequeue(self::$testQueueName);
        $this->assertTrue(DiskQueue::isEmpty(self::$testQueueName), "Queue should be empty after removing all items.");
    }
}
