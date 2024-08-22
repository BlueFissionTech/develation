<?php
namespace BlueFission\Async;

use BlueFission\Arr;
use parallel\{Runtime, Future};
use BlueFission\Behavioral\Behaviors\Event;

/**
 * The Thread class extends Async functionality to handle concurrent tasks using PHP's parallel extension.
 * This class enables true multithreading, allowing code to run in parallel.
*/
class Thread extends Async {
    /**
	 * @var string The bootstrap file path to initialize the parallel runtime environment.
	*/
    protected static string $bootstrap = '';

    /**
	 * Executes a task in parallel ( simulating a thread ).
	 * 
	 * Converts the provided task into a callable closure if needed and runs it in a separate thread.
	 * 
	 * @param callable|array $task The task to execute concurrently. If an array is passed, it will be converted to a closure.
	 * @param int $priority The priority level of the task, with higher numbers executed first.
	 * @return Promise A promise that resolves when the task completes or fails.
	*/
    public static function do( $task, $priority = 10 ) {
        // If the task is an array, convert it to a closure
        if ( Arr::is( $task ) ) {
            $taskCopy = $task;

            // Convert the callable array to a closure
            $task = \Closure::fromCallable( $task );

            // If the first element is an object, bind the closure to it
            if ( isset( $taskCopy[0] ) && is_object( $taskCopy[0] ) ) {
                $task->bindTo( $taskCopy[0], $taskCopy[0] );
            }
        }

        // Create a new promise to handle the task execution
        $promise = new Promise( function( $resolve, $reject ) use ( $task ) {
            // Initialize the parallel runtime environment
            $runtime = ( self::$bootstrap ? new Runtime( self::$bootstrap ) : new Runtime() );

            try {
                // Run the task in parallel and store the future result
                $future = $runtime->run( $task, [$resolve, $reject] );

                // Retrieve the result once the task has completed
                $result = $future->value();

                // Resolve the promise with the result
                $resolve( $result );
            } catch ( \Exception $e ) {
                // Reject the promise if an exception occurs
                $reject( $e );
            }
        }, self::instance() );

        // Store the promise and execute based on priority
        self::keep( $promise, $priority );

        // Return the created promise
        return $promise;
    }

    /**
	 * Provides a success handler to be used with the promise resolution.
	 * 
	 * @return \Closure A closure that handles successful task completion.
	*/
    public static function resolve() {
        return function( $response ) {
            // Success handler: returns the response when the thread completes successfully
            return $response;
        };
    }

    /**
	 * Provides an error handler to be used with the promise rejection.
	 * 
	 * @return \Closure A closure that throws an exception on task failure.
	*/
    public static function reject() {
        return function( $error ) {
            // Error handler: throws an exception when the thread fails
            throw new \Exception( "Thread failed: $error" );
        };
    }

    /**
	 * Sets the bootstrap file for initializing the parallel runtime environment.
	 * 
	 * @param string $bootstrap The file path of the bootstrap file.
	*/
    public static function setBootstrap( $bootstrap ) {
        // Assign the provided bootstrap file path to the static property
        self::$bootstrap = $bootstrap;
    }
}