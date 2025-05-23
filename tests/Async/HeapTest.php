<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\SplPriorityQueue;
use BlueFission\Async\Heap;

class HeapTest extends TestCase
{
    public function testAddingAndExecutingTasks()
    {
        // Clear Heap tasks before each test
        Heap::setQueue(SplPriorityQueue::class);

        $result = [];

        // Define some tasks
        $task1 = function () use (&$result) { $result[] = 'task1'; };
        $task2 = function () use (&$result) { $result[] = 'task2'; };
        $task3 = function () use (&$result) { $result[] = 'task3'; };

        // Add tasks to the heap with different priorities
        Heap::do($task3, 1); // Lower priority
        Heap::do($task2, 10); // Higher priority
        Heap::do($task1, 5); // Medium priority

        // Run the heap to process all tasks
        Heap::run();

        // Check if tasks were executed in the correct order
        $expectedOrder = ['task2', 'task1', 'task3'];
        $this->assertEquals($expectedOrder, $result, 'Tasks should execute in the correct priority order');
    }
}
