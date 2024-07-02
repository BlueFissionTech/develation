<?php
namespace BlueFission\Async;

use parallel\{Runtime, Future};
use BlueFission\Behavioral\Behaviors\Event;

/**
 * The Thread class extends the Async functionality to handle true concurrent tasks using PHP's parallel extension.
 */
class Thread extends Async {
    /**
     * Executes a function in parallel (simulating a thread).
     * 
     * @param callable $function The function to execute.
     * @param array $args Arguments to be passed to the function.
     * @return Promise A promise that resolves when the parallel execution completes.
     */
    public static function do($function, $priority = 0, $args = []) {
        $task = function() use ($function, $args) {
            $runtime = new Runtime();
            $future = $runtime->run($function, $args);
            $result = $future->value();
            return $result;
        };

        return static::exec($task, $priority);
    }
}
