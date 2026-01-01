<?php

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Storage\MySql;
use BlueFission\Data\Queues\DBQueue;
use BlueFission\Connections\Database\MySQLLink;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../../Support/TestEnvironment.php';

class DBQueueTest extends TestCase {
    private $storage;
    private $queueName;

    protected function setUp(): void {
        $enabled = strtolower((string)getenv('DEV_ELATION_DBQUEUE_TESTS'));
        if (!in_array($enabled, ['1', 'true', 'yes'], true)) {
            $this->markTestSkipped('DBQueue tests are disabled');
        }

        $config = TestEnvironment::mysqlConfig();
        if (!class_exists('mysqli') || !$config) {
            $this->markTestSkipped('DBQueue tests require mysqli and DEV_ELATION_MYSQL_* env vars');
        }

        $link = new MySQLLink([
            'target' => $config['host'],
            'username' => $config['user'],
            'password' => $config['pass'],
            'database' => $config['db'],
            'port' => $config['port'],
        ]);
        $link->open();
        if ($link->status() !== MySQLLink::STATUS_CONNECTED) {
            $this->markTestSkipped('MySQL connection unavailable');
        }

        $this->queueName = 'test_queue_' . uniqid();
        $this->storage = new MySql([
            'location' => $config['db'],
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
