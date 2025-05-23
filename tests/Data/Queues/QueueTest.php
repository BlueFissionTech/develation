<?php

namespace BlueFission\Tests\Data\Queues;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\Queue;

class QueueTest extends TestCase
{
    protected function setUp(): void
    {
        Queue::setMode(Queue::FIFO); // Default to FIFO for most tests

        while (!Queue::isEmpty('testQueue')) {
            Queue::dequeue('testQueue');
        }
    }

    public function testQueueIsEmptyInitially()
    {
        $this->assertTrue(Queue::isEmpty('testQueue'), "Queue should be empty initially.");
    }

    public function testEnqueueAndDequeueItems()
    {
        Queue::enqueue('testQueue', 'firstItem');
        Queue::enqueue('testQueue', 'secondItem');

        $this->assertFalse(Queue::isEmpty('testQueue'), "Queue should not be empty after adding items.");

        $firstDequeued = Queue::dequeue('testQueue');
        $secondDequeued = Queue::dequeue('testQueue');

        $this->assertEquals('firstItem', $firstDequeued, "The first dequeued item should be 'firstItem'.");
        $this->assertEquals('secondItem', $secondDequeued, "The second dequeued item should be 'secondItem'.");
    }

    public function testQueueSupportsFILOMode()
    {
        Queue::setMode(Queue::FILO);
        Queue::enqueue('testQueue', 'firstItem');
        Queue::enqueue('testQueue', 'secondItem');

        $dequeued = Queue::dequeue('testQueue');
        $this->assertEquals('secondItem', $dequeued, "The dequeued item should be 'secondItem' when in FILO mode.");
    }

    public function testDequeueWithLimits()
    {
        Queue::enqueue('testQueue', 'firstItem');
        Queue::enqueue('testQueue', 'secondItem');
        Queue::enqueue('testQueue', 'thirdItem');

        Queue::setMode(Queue::FIFO);
        $limitedItems = Queue::dequeue('testQueue', 1, 2);

        $this->assertContains('secondItem', $limitedItems, "Dequeue should include 'secondItem'.");
        $this->assertContains('thirdItem', $limitedItems, "Dequeue should include 'thirdItem'.");
        $this->assertCount(2, $limitedItems, "Only two items should be dequeued.");
    }

    public function testQueueIsEmptyAfterClearing()
    {
        Queue::enqueue('testQueue', 'item');
        Queue::dequeue('testQueue');
        $this->assertTrue(Queue::isEmpty('testQueue'), "Queue should be empty after removing all items.");
    }
}
