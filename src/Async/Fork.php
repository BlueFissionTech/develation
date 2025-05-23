<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use Exception;

/**
 * Class Fork for managing PHP process forking.
 * This class extends the Async abstract class and provides specific implementations for forking PHP processes.
 */
class Fork extends Async
{
    /**
     * Forks the current PHP process to execute a task in a separate process.
     *
     * @param callable $task      The task to execute in the forked process. This task should accept two parameters: resolve and reject.
     * @param int      $priority  The priority of the task; higher values are processed earlier.
     * @param int|null $processId Will be set with the child process ID if provided.
     * @return Promise
     * @throws Exception If pcntl_fork is unavailable
     */
    public static function do($task, $priority = 10, &$processId = null): Promise
    {
        if (!function_exists('pcntl_fork')) {
            throw new Exception("The pcntl extension is required to fork processes.");
        }

        $promise = new Promise(function ($resolve, $reject) use ($task, &$processId) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                // Fork failed
                $reject("Could not fork the process.");
            } elseif ($pid) {
                // Parent process
                $status = null;
                pcntl_waitpid($pid, $status, WNOHANG); // Non-blocking wait
                $processId = $pid;
            } else {
                // Child process
                call_user_func($task, $resolve, $reject);
                exit(0); // Important: ensures child process exits
            }
        }, self::instance());

        self::keep($promise, $priority);
        return $promise;
    }

    /**
     * Returns a callable that waits for a child process and returns its status.
     *
     * @return callable
     */
    public static function resolve(): callable
    {
        return function ($pid) {
            pcntl_waitpid($pid, $status, WNOHANG);
            if (pcntl_wifexited($status)) {
                $exitStatus = pcntl_wexitstatus($status);
                return "Child exited with status $exitStatus";
            }
            return "Child is still running or exited abnormally.";
        };
    }

    /**
     * Returns a callable that throws an exception with the provided error message.
     *
     * @return callable
     */
    public static function reject(): callable
    {
        return function ($error) {
            throw new Exception("Fork failed: $error");
        };
    }
}
