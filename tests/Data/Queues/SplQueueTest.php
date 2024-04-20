<?php

namespace BlueFission\Tests\Data\Queues;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\SplQueue;

class SplQueueTest extends TestCase {
    private $queue;

    protected function setUp(): void {
    }

    public function testQueueIsEmptyInitially() {
        $this->assertTrue(SplQueue::isEmpty('testChannel'), "Queue should be empty initially.");
    }

    public function testEnqueueDequeueItems() {
        SplQueue::enqueue('item1', 'testChannel');
        SplQueue::enqueue('item2', 'testChannel');
        $this->assertFalse(SplQueue::isEmpty('testChannel'), "Queue should not be empty after enqueueing items.");

        $firstItem = SplQueue::dequeue('testChannel');
        $secondItem = SplQueue::dequeue('testChannel');

        $this->assertEquals('item1', $firstItem, "The first dequeued item should be 'item1'.");
        $this->assertEquals('item2', $secondItem, "The second dequeued item should be 'item2'.");
        $this->assertTrue(SplQueue::isEmpty('testChannel'), "Queue should be empty after dequeuing all items.");
    }

    public function testDifferentChannelsAreIndependent() {
        SplQueue::enqueue('channel1Item', 'channel1');
        SplQueue::enqueue('channel2Item', 'channel2');

        $this->assertEquals('channel1Item', SplQueue::dequeue('channel1'), "Dequeue from 'channel1' should yield 'channel1Item'.");
        $this->assertEquals('channel2Item', SplQueue::dequeue('channel2'), "Dequeue from 'channel2' should yield 'channel2Item'.");
    }
}
