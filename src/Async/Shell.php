<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\System\Process;

/**
 * Class Shell for managing shell commands asynchronously.
 * This class extends the Async abstract class and provides specific implementations for executing shell commands.
 */
class Shell extends Async {

    /**
     * Executes a shell command asynchronously.
     * 
     * @param string $_command The shell command to execute.
     * @param int $_priority The priority of the task; higher values are processed earlier.
     * @return Shell The instance of the Shell class.
     */
    public static function do($_command, $_priority = 10) {
        $_function = function() use ($_command) {
            $_process = new Process($_command);
            $_process->start();
            while ($_status = $_process->status()) {
                if (!$_status) {
                    // If process is no longer running, break the loop
                    break;
                }
                // Here you could add code to process any output as it's received
                $_output = $_process->output();
                yield $_output;
            }
            // Optionally handle any final output or cleanup
            $_output = $_process->output(); // Get any remaining output
            $_process->close();
            yield $_output;
        };

        return static::exec($_function, $_priority);
    }

    /**
     * Optional: Implement additional methods to handle process outputs, errors, or specific shell functionalities.
     */
}
