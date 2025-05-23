<?php

namespace BlueFission\Async;

use BlueFission\Arr;
use parallel\{Runtime, Future};
use BlueFission\Behavioral\Behaviors\Event;

/**
 * The Thread class extends Async to handle concurrent tasks using PHP's parallel extension.
 */
class Thread extends Async
{
    protected static string $_bootstrap = '';

    /**
     * Executes a task in parallel using the parallel extension.
     *
     * @param callable|array $task The task to execute. Can be a Closure or [object, method] pair.
     * @param int $priority The priority for task execution.
     * @return Promise Resolves when the task completes.
     */
    public static function do($task, int $priority = 10): Promise
    {
        if (Arr::is($task)) {
            $taskCopy = $task;
            $task = \Closure::fromCallable($task);
            if (isset($taskCopy[0]) && is_object($taskCopy[0])) {
                $task = $task->bindTo($taskCopy[0], $taskCopy[0]);
            }
        }

        $promise = new Promise(function ($resolve, $reject) use ($task) {
            $runtime = self::$_bootstrap
                ? new Runtime(self::$_bootstrap)
                : new Runtime();

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

    /**
     * Default resolve handler.
     *
     * @return callable
     */
    public static function resolve(): callable
    {
        return function ($response) {
            return $response;
        };
    }

    /**
     * Default reject handler.
     *
     * @return callable
     */
    public static function reject(): callable
    {
        return function ($error) {
            throw new \Exception("Thread failed: $error");
        };
    }

    /**
     * Sets the bootstrap script to initialize the parallel runtime.
     *
     * @param string $bootstrap
     * @return void
     */
    public static function setBootstrap(string $bootstrap): void
    {
        self::$_bootstrap = $bootstrap;
    }
}

/**
 * Improvement Summary:
 * - Added `Promise` return type for `do()` for clarity
 * - Added `callable` return types to `resolve()` and `reject()` to match expectations
 * - Added `string` type to `setBootstrap()` for strict typing
 * - Added full PHPDoc to class and all methods for clarity and IDE support
 * - Used `bindTo()` properly when task is given as [object, method]
 * - Reorganized logic for better readability and structure
 */
