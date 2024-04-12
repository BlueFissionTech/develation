<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

/**
 * Class Heap for managing a stack of tasks.
 * This class extends the Async abstract class to provide specific implementations for task stacking.
 */
class Heap extends Async {

    /**
     * Adds a task to the stack.
     * 
     * @param callable $function The function that represents the task to be executed.
     * @param int $priority The priority of the task; higher values are processed earlier.
     * @return Heap The instance of the Heap class.
     */
    public static function do($function, $priority = 10) {
        return static::exec($function, $priority);
    }

    /**
     * Processes all tasks in the stack according to their priorities.
     * This method overrides the run method from Async to ensure tasks are processed in a specific order if needed.
     */
    public static function run() {
        $instance = self::instance();
        $instance->perform(State::RUNNING);

        while (!$instance->tasks()->isEmpty()) {
            $task = $instance->tasks()->dequeue();
            $instance->monitorStart($task);
            $generator = $task();
            while ($generator->valid()) {
                if ($generator->current() === null) {
                    $instance->perform(Event::FAILURE);
                    break;
                }
                $generator->next();
            }
            $instance->monitorEnd($task);
            $instance->perform(Event::PROCESSED);
        }

        $instance->halt(State::RUNNING);
        $instance->perform(Event::COMPLETE);
        $instance->halt(State::PROCESSING);
    }

    /**
     * Optional: Implement additional stack-specific methods like task dependency management, delay between tasks, etc.
     */

    // Additional methods can be implemented here
}
