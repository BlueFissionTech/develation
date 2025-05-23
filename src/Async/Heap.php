<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Async\Promise;

/**
 * Class Heap for managing a stack of tasks.
 * Designed for sequential async task execution (LIFO behavior).
 */
class Heap extends Async {

    /**
     * Adds a task to the stack and returns its Promise.
     *
     * @param callable $function The function representing the task.
     * @param int $priority Priority of the task; higher values execute earlier.
     * @return Promise
     */
    public static function do($function, $priority = 10): Promise {
        return static::exec($function, $priority);
    }

    /**
     * Optional: Implement additional stack-specific methods
     * like:
     *  - delaying between executions
     *  - managing dependencies
     *  - task throttling
     */
}
