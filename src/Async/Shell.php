<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\System\Process;

/**
 * Class Shell
 * 
 * This class manages the asynchronous execution of shell commands. 
 * It extends the Async class to support the execution of system-level commands 
 * while integrating with the event-driven architecture of the Async framework.
*/
class Shell extends Async {

    /**
	 * Executes a shell command asynchronously.
	 *
	 * This method launches a shell command in a new process and continuously monitors
	 * its status, yielding output as it is received. It automatically handles the process
	 * completion and cleanup.
	 *
	 * @param string $command The shell command to execute.
	 * @param int $priority The priority of the task in the queue; higher values are processed first.
	 * @return Shell Returns the Shell instance to allow method chaining.
	*/
    public static function do( $command, $priority = 10 ) {
        // Define the asynchronous function that will handle the shell command execution
        $function = function() use ( $command ) {
            // Create a new process instance for the shell command
            $process = new Process( $command );
            $process->start(); // Start the process

            // Continuously check the process status
            while ( $status = $process->status() ) {
                if ( !$status ) {
                    // If the process has completed, exit the loop
                    break;
                }

                // Optionally, capture and yield output from the process as it is generated
                $output = $process->output(); // Get the current output
                yield $output; // Yield the output to the caller
            }

            // Capture any remaining output after the process has completed
            $output = $process->output(); // Fetch the final output
            $process->close(); // Close the process resources

            yield $output; // Yield the final output
        };

        // Execute the asynchronous function with the specified priority
        return static::exec( $function, $priority );
    }

    /**
	 * Optionally, additional methods could be added here to handle process-specific behaviors, such as processing errors, managing input, or handling specific shell functionalities.
	*/
}
