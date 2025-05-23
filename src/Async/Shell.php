<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\System\Process;

/**
 * Class Shell for managing shell commands asynchronously.
 * This class extends the Async abstract class and provides specific implementations for executing shell commands.
 */
class Shell extends Async
{
    /**
     * Executes a shell command asynchronously.
     *
     * @param string $command The shell command to execute.
     * @param int $priority The priority of the task; higher values are processed earlier.
     * @return Shell The instance of the Shell class.
     */
    public static function do($command, $priority = 10)
    {
        $function = function () use ($command) {
            $process = new Process($command);
            $process->start();
            while ($status = $process->status()) {
                if (!$status) {
                    // If process is no longer running, break the loop
                    break;
                }
                // Here you could add code to process any output as it's received
                $output = $process->output();
                yield $output;
            }
            // Optionally handle any final output or cleanup
            $output = $process->output(); // Get any remaining output
            $process->close();
            yield $output;
        };

        return static::exec($function, $priority);
    }

    /**
     * Optional: Implement additional methods to handle process outputs, errors, or specific shell functionalities.
     */
}
