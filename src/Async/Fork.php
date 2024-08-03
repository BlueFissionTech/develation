<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

/**
 * Class Fork for managing PHP process forking.
 * This class extends the Async abstract class and provides specific implementations for forking PHP processes.
 */
class Fork extends Async {

    /**
     * Forks the current PHP process to execute a task in a separate process.
     *
     * @param callable $task The task to execute in the forked process. This task should accept two parameters: resolve and reject functions.
     * @param int $priority The priority of the task; higher values are processed earlier.
     * @return Promise The promise associated with the asynchronous operation.
     */
    public static function do($task, $priority = 10, &$processId = null) {
        if (!function_exists('pcntl_fork')) {
            throw new Exception("The pcntl extension is required to fork processes.");
        }

        $promise = new Promise(function($resolve, $reject) use ($task, &$processId) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                // Handle error: failed to fork
                $reject("Could not fork the process.");
            } elseif ($pid) {
                // Parent process will reach this branch
                // Use non-blocking wait to check child process status
                $status = null;
                pcntl_waitpid($pid, $status, WNOHANG); // Non-blocking wait
                // You might want to implement a more robust checking or signaling mechanism here
                if ($processId) {
                    $processId = $pid;
                }
            } else {
                // Child process will execute the task
                call_user_func($task, $resolve, $reject);
                exit(0); // Ensure the child exits after task completion
            }
        }, self::instance());

        self::keep($promise, $priority);

        return $promise;
    }

    public static function resolve() {
        return function($pid) {
            // Success handler: child process has started successfully
            \pcntl_waitpid($pid, $status, WNOHANG); // Optionally use WNOHANG to avoid blocking
            if (\pcntl_wifexited($status)) {
                $exitStatus = \pcntl_wexitstatus($status);
                return "Child exited with status $exitStatus";
            }
        };
    }

    public static function reject() {
        return function($error) {
            // Error handler: handle fork failure
            throw new \Exception("Fork failed: $error");
        };
    }

    /**
     * Optionally, implement additional methods to handle specific forking scenarios, signaling, or inter-process communication.
     */
}
