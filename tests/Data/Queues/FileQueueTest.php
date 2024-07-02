<?php

namespace BlueFission\Tests\Data\Queues;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\FileQueue;

class FileQueueTest extends TestCase {
    private static $testQueueName = 'testQueue';

    protected function setUp(): void {
        FileQueue::setMode(FileQueue::FIFO); // Default to FIFO for testing
    }

    protected function tearDown(): void {
        // Clear queue after each test
        if (!FileQueue::isEmpty(self::$testQueueName)) {
            while (!FileQueue::isEmpty(self::$testQueueName)) {
                FileQueue::dequeue(self::$testQueueName);
            }
        }
    }

    public function testQueueIsEmptyInitially() {
        $this->assertTrue(FileQueue::isEmpty(self::$testQueueName), "Queue should be empty initially.");
    }

    public function testEnqueueAndDequeueItems() {
        FileQueue::enqueue(self::$testQueueName, 'firstItem');
        FileQueue::enqueue(self::$testQueueName, 'secondItem');

        $this->assertFalse(FileQueue::isEmpty(self::$testQueueName), "Queue should not be empty after adding items.");

        $firstDequeued = FileQueue::dequeue(self::$testQueueName);
        $secondDequeued = FileQueue::dequeue(self::$testQueueName);

        $this->assertEquals('firstItem', $firstDequeued, "The first dequeued item should be 'firstItem'.");
        $this->assertEquals('secondItem', $secondDequeued, "The second dequeued item should be 'secondItem'.");
    }

    public function testQueueSupportsFILOMode() {
        FileQueue::setMode(FileQueue::FILO);
        FileQueue::enqueue(self::$testQueueName, 'firstItem');
        FileQueue::enqueue(self::$testQueueName, 'secondItem');

        $dequeued = FileQueue::dequeue(self::$testQueueName);
        $this->assertEquals('secondItem', $dequeued, "The dequeued item should be 'secondItem' when in FILO mode.");
    }

    public function testDequeueWithLimits() {
        FileQueue::enqueue(self::$testQueueName, 'firstItem');
        FileQueue::enqueue(self::$testQueueName, 'secondItem');
        FileQueue::enqueue(self::$testQueueName, 'thirdItem');

        $limitedItems = FileQueue::dequeue(self::$testQueueName, 1, 2);

        $this->assertContains('secondItem', $limitedItems, "Dequeue should include 'secondItem'.");
        $this->assertCount(2, $limitedItems, "Only one item should be dequeued.");
    }

    public function testQueueIsEmptyAfterClearing() {
        FileQueue::enqueue(self::$testQueueName, 'item');
        FileQueue::dequeue(self::$testQueueName);
        $this->assertTrue(FileQueue::isEmpty(self::$testQueueName), "Queue should be empty after removing all items.");
    }
}
