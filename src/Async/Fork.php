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
     * @param callable $_task The task to execute in the forked process. This task should accept two parameters: resolve and reject functions.
     * @param int $_priority The priority of the task; higher values are processed earlier.
     * @return Promise The promise associated with the asynchronous operation.
     */
    public static function do($_task, $_priority = 10, &$_processId = null) {
        if (!function_exists('pcntl_fork')) {
            throw new Exception("The pcntl extension is required to fork processes.");
        }

        $_promise = new Promise(function($_resolve, $_reject) use ($_task, &$_processId) {
            $_pid = pcntl_fork();

            if ($_pid == -1) {
                // Handle error: failed to fork
                $_reject("Could not fork the process.");
            } elseif ($_pid) {
                // Parent process will reach this branch
                // Use non-blocking wait to check child process status
                $_status = null;
                pcntl_waitpid($_pid, $_status, WNOHANG); // Non-blocking wait
                // You might want to implement a more robust checking or signaling mechanism here
                if ($_processId) {
                    $_processId = $_pid;
                }
            } else {
                // Child process will execute the task
                call_user_func($_task, $_resolve, $_reject);
                exit(0); // Ensure the child exits after task completion
            }
        }, self::instance());

        self::keep($_promise, $_priority);

        return $_promise;
    }

    public static function resolve() {
        return function($_pid) {
            // Success handler: child process has started successfully
            \pcntl_waitpid($_pid, $_status, WNOHANG); // Optionally use WNOHANG to avoid blocking
            if (\pcntl_wifexited($_status)) {
                $_exitStatus = \pcntl_wexitstatus($_status);
                return "Child exited with status $_exitStatus";
            }
        };
    }

    public static function reject() {
        return function($_error) {
            // Error handler: handle fork failure
            throw new \Exception("Fork failed: $_error");
        };
    }

    /**
     * Optionally, implement additional methods to handle specific forking scenarios, signaling, or inter-process communication.
     */
}
