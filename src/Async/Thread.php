<?php
namespace BlueFission\Async;

use BlueFission\Arr;
use parallel\{Runtime, Future};
use BlueFission\Behavioral\Behaviors\Event;

/**
 * The Thread class extends the Async functionality to handle true concurrent tasks using PHP's parallel extension.
 */
class Thread extends Async {
    protected static string $_bootstrap = '';

    /**
     * Executes a function in parallel (simulating a thread).
     * 
     * @param callable $function The function to execute.
     * @param array $args Arguments to be passed to the function.
     * @return Promise A promise that resolves when the parallel execution completes.
     */
    public static function do($_task, $_priority = 10) {
        if (Arr::is($_task)) {
            $_taskCopy = $_task;
            $_task = \Closure::fromCallable($_task);
            if ( isset($_taskCopy[0]) && is_object($_taskCopy[0]) ) {
                $_task->bindTo($_taskCopy[0], $_taskCopy[0]);
            }
        }

        $promise = new Promise(function($resolve, $reject) use ($_task) {
            $_runtime = ( self::$_bootstrap ? new Runtime(self::$_bootstrap) : new Runtime() );

            try {
                $_future = $_runtime->run($_task, [$resolve, $reject]);
                $_result = $_future->value();
                $resolve($_result);
            } catch (\Exception $e) {
                $reject($e);
            }
        }, self::instance());

        self::keep($promise, $_priority);

        return $promise;
    }

    public static function resolve() {
        return function($_response) {
            // Success handler: handle thread success
            return $_response;
        };
    }

    public static function reject() {
        return function($error) {
            // Error handler: handle thread failure
            throw new \Exception("Thread failed: $error");
        };
    }

    public static function setBootstrap($_bootstrap) {
        self::$_bootstrap = $_bootstrap;
    }
}