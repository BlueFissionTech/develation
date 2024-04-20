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
     * @param callable $task The task to execute in the forked process.
     * @param int $priority The priority of the task; higher values are processed earlier.
     * @return Fork The instance of the Fork class.
     */
    public static function do($task, $priority = 10) {

        if (!function_exists('pcntl_fork')) {
            throw new \Exception("The pcntl extension is required to fork processes.");
        }


        $function = function() use ($task) {
            $pid = \pcntl_fork();

            if ($pid == -1) {
                // Handle error: failed to fork
                throw new \Exception("Could not fork the process.");
            } elseif ($pid) {
                // Parent process will reach this branch
                \pcntl_wait($status); // Optional: Wait for child to exit
                yield "Child process completed";
            } else {
                // Child process will execute the task
                call_user_func($task);
                exit(0); // Ensure the child exits after task completion
            }
        };

        return static::exec($function, $priority);
    }

    /**
     * Optionally, implement additional methods to handle specific forking scenarios, signaling, or inter-process communication.
     */
}
