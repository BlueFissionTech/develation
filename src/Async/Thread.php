<?php
namespace BlueFission\Async;

use BlueFission\Arr;
use parallel\{Runtime, Future};
use BlueFission\Behavioral\Behaviors\Event;

/**
 * The Thread class extends the Async functionality to handle true concurrent tasks using PHP's parallel extension.
 */
class Thread extends Async {
    protected static string $bootstrap = '';

    /**
     * Executes a function in parallel (simulating a thread).
     * 
     * @param callable $function The function to execute.
     * @param array $args Arguments to be passed to the function.
     * @return Promise A promise that resolves when the parallel execution completes.
     */
    public static function do($task, $priority = 10) {
        if (Arr::is($task)) {
            $taskCopy = $task;
            $task = \Closure::fromCallable($task);
            if ( isset($taskCopy[0]) && is_object($taskCopy[0]) ) {
                $task->bindTo($taskCopy[0], $taskCopy[0]);
            }
        }

        $promise = new Promise(function($resolve, $reject) use ($task) {
            $runtime = ( self::$bootstrap ? new Runtime(self::$bootstrap) : new Runtime() );

            try {
                $future = $runtime->run($task, [$resolve, $reject]);
                $result = $future->value();
                $resolve($result);
            } catch (\Exception $e) {
                $reject($e);
            }
        }, self::instance());

        self::keep($promise, $priority);

        return $promise;
    }

    public static function resolve() {
        return function($response) {
            // Success handler: handle thread success
            return $response;
        };
    }

    public static function reject() {
        return function($error) {
            // Error handler: handle thread failure
            throw new \Exception("Thread failed: $error");
        };
    }

    public static function setBootstrap($bootstrap) {
        self::$bootstrap = $bootstrap;
    }
}