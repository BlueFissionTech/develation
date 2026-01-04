<?php

namespace BlueFission\Tests\Data\Queues;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\SplPriorityQueue;

class SplPriorityQueueTest extends TestCase
{
    private $queue;

    protected function setUp(): void
    {
    }

    public function testQueueIsEmptyInitially()
    {
        $this->assertTrue(SplPriorityQueue::isEmpty('testChannel'), "Queue should be empty initially.");
    }

    public function testEnqueueDequeueItems()
    {
        SplPriorityQueue::enqueue(['data' => 'lowPriority', 'priority' => 1], 'testChannel');
        SplPriorityQueue::enqueue(['data' => 'highPriority', 'priority' => 10], 'testChannel');

        $this->assertFalse(SplPriorityQueue::isEmpty('testChannel'), "Queue should not be empty after enqueueing items.");

        $highPriorityItem = SplPriorityQueue::dequeue('testChannel');
        $lowPriorityItem = SplPriorityQueue::dequeue('testChannel');

        $this->assertEquals('highPriority', $highPriorityItem, "The first dequeued item should be 'highPriority' due to higher priority.");
        $this->assertEquals('lowPriority', $lowPriorityItem, "The second dequeued item should be 'lowPriority'.");
    }

    public function testDifferentChannelsAreIndependent()
    {
        SplPriorityQueue::enqueue(['data' => 'channel1Item', 'priority' => 5], 'channel1');
        SplPriorityQueue::enqueue(['data' => 'channel2Item', 'priority' => 5], 'channel2');

        $this->assertEquals('channel1Item', SplPriorityQueue::dequeue('channel1'), "Dequeue from 'channel1' should yield 'channel1Item'.");
        $this->assertEquals('channel2Item', SplPriorityQueue::dequeue('channel2'), "Dequeue from 'channel2' should yield 'channel2Item'.");
    }
}
