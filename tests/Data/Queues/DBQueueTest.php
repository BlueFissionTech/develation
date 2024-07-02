<?php

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Storage\MySql;
use BlueFission\Data\Queues\DBQueue;

class DBQueueTest extends TestCase {
    private $storage;
    private $queueName;

    protected function setUp(): void {
        $this->queueName = 'test_queue';
        $this->storage = new MySql([
            'location' => 'localhost',
            'name' => $this->queueName,
            'fields' => ['message_id', 'channel', 'message'],
            'key' => 'message_id',
        ]);

        // Assuming DBQueue::setStorage could accept different storage types for testing
        DBQueue::setStorage($this->storage);
        $this->storage->activate(); // Ensure storage is activated
    }

    public function testIsEmptyInitially() {
        $isEmpty = DBQueue::isEmpty($this->queueName);
        $this->assertTrue($isEmpty, "Queue should be initially empty.");
    }

    public function testEnqueueAndDequeue() {
        $item = "Hello, World!";
        DBQueue::enqueue($this->queueName, $item);
        $isEmpty = DBQueue::isEmpty($this->queueName);
        $this->assertFalse($isEmpty, "Queue should not be empty after enqueue.");

        $dequeuedItem = DBQueue::dequeue($this->queueName);
        $this->assertEquals($item, $dequeuedItem, "The dequeued item should match the enqueued.");

        $isEmpty = DBQueue::isEmpty($this->queueName);
        $this->assertTrue($isEmpty, "Queue should be empty after dequeue.");
    }

    public function testDequeueEmptyQueue() {
        $dequeuedItem = DBQueue::dequeue($this->queueName);
        $this->assertNull($dequeuedItem, "Dequeueing an empty queue should return null.");
    }

    protected function tearDown(): void {
        // Clean up if needed
        $this->storage->clear()->order('message_id', 'DESC')->channel = $this->queueName;
        while ($this->storage->read()->id()) {
            $this->storage->delete();
        }
    }
}
