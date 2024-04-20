<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\SplPriorityQueue;
use BlueFission\Async\Fork;

class ForkTest extends TestCase {
    public function testProcessForking() {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('The pcntl extension is not available');
        }

        Fork::setQueue(SplPriorityQueue::class);

        $result = [];

        // Mock task that modifies an array
        $task = function() use (&$result) {
            $result[] = 'executed';
        };

        // Since actual forking cannot be tested easily in PHPUnit (because it would fork the test runner process),
        // we can check if the method completes without errors and simulate the expected behavior.
        Fork::do($task);

        // Run the fork tasks
        Fork::run();

        // We check if the task was supposedly "executed"
        // In real unit tests, we might only check if the right methods were called or the right classes were used.
        $this->assertNotEmpty($result, 'The task should modify the result array');
        $this->assertEquals('executed', $result[0], 'The task should execute and modify the result array as expected');
    }
}
