<?php

namespace BlueFission\Async;

/**
 * Interface IAsync defines the contract for asynchronous operations.
 * Implementing classes should provide mechanisms for executing tasks and running queued operations.
*/
interface IAsync {
	/**
	 * Executes a function asynchronously with provided arguments.
	 *
	 * @param callable $function The function to execute asynchronously.
	 * @param array $args An optional array of arguments to pass to the function.
	 * @return mixed The result of the asynchronous execution.
	*/
    public static function exec( $function, $args = [] );
	
	/**
	 * Runs all queued tasks.
	 *
	 * @return void
	 */
    public static function run();
}