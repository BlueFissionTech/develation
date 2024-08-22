<?php
namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\IBehavioral;
use BlueFission\Data\Queues\IQueue;
use BlueFission\Data\Queues\SplPriorityQueue;
use BlueFission\Data\Log;
use BlueFission\IObj;
use BlueFission\Obj;
use BlueFission\Arr;

/**
 * The Async class provides a framework for executing asynchronous tasks using a behavioral pattern.
 * It allows tasks to be queued and executed without blocking the main thread of execution.
*/
abstract class Async extends Obj implements IAsync, IObj, IBehavioral {
	use Behaves {
		Behaves::__construct as private __behavesConstruct;
	}

	/**
	 * Singleton instance of the Async class.
	 * @var Async|null
	*/
	private static $instance = null;

	/**
	 * 
	 * Queue that holds all the tasks to be executed asynchronously.
	 * 
	 * @var IQueue|null
	*/
	protected static $tasks;

	/**
	 * Configuration settings for the asynchronous tasks.
	 * 
	 * @var array
	*/
	protected static $config = [];

	/**
	 * Queue implementation used for storing tasks.
	 * 
	 * @var string|null
	*/
	protected static $queue;

	/**
	 * The name of the queue where tasks will be stored.
	 * 
	 * @var string
	*/
	protected static $queueName = 'async_queue';

	/**
	 * Variable to track task timing.
	 * 
	 * @var int
	*/
	protected static $time;

	/**
	 * Private constructor to prevent creating a new instance outside of the class.
	*/
	private function __construct() {
		parent::__construct();
		$this->__behavesConstruct(); // Initialize behavioral traits

		// Registering events for the lifecycle of the asynchronous process
		$this->behavior( new Event( Event::LOAD ) );
		$this->behavior( new Event( Event::UNLOAD ) );
		$this->behavior( new Event( Event::COMPLETE ) );
		$this->behavior( new Event( Event::ERROR ) );

		// Initializing the task queue
		self::$tasks = self::getQueue();
		self::$config = self::getConfig();
	}

	/**
	 * Sets the queue implementation to be used for task management.
	 * 
	 * @param string $queueClass The name of the queue class implementing the IQueue interface.
	*/
	public static function setQueue( string $queueClass ) {
		// Assign the queue class to the static property for later use
		self::$queue = $queueClass;
	}

	/**
	 * Returns the task queue.
	 * 
	 * @return IQueue|null The queue holding tasks for asynchronous execution.
	*/
	private function tasks() {
		// Return the static tasks property holding the task queue
		return self::$tasks;
	}

	/**
	 * Returns the queue instance, initializing it if necessary.
	 * 
	 * @return string The queue class name, defaulting to SplPriorityQueue if not set.
	*/
	protected static function getQueue(): string {
		// Initialize the queue with a default implementation if not already set
		if ( !self::$queue ) {
			self::$queue = SplPriorityQueue::class; // Default to SplPriorityQueue if no custom queue provided
		}
		return self::$queue;
	}

	/**
	 * Sets configuration options for the asynchronous system.
	 * 
	 * @param array $config An array of configuration settings.
	*/
	public static function setConfig( array $config ) {
		// Assign the provided configuration to the static config property
		self::$config = $config;
	}

	/**
	 * Retrieves the current configuration settings.
	 * 
	 * @return array The configuration settings for the asynchronous system.
	*/
	protected static function getConfig(): array {
		// Merge any default configuration options with the custom settings provided
		return Arr::merge( self::$config, [
			'max_concurrency' => 10,
			'default_timeout' => 30,
			'retry_strategy' => 'simple',
			'timeout' => 300,
			'notifyURL' => 'http://localhost:8080',
		] );
	}

	/**
	 * Provides access to the singleton instance of the Async class.
	 * 
	 * @return Async|null The singleton instance of the Async class.
	*/
	protected static function instance() {
		// Check if an instance of Async already exists, and if not, create one
		if ( self::$instance === null ) {
			self::$instance = new static();
			self::$instance->perform( Event::INITIALIZED ); // Perform the initialization event
		}
		return self::$instance;
	}

	/**
	 * Executes a function asynchronously.
	 * 
	 * @param callable $function The function to execute.
	 * @param int $priority The priority level for the task.
	 * @return Promise The promise representing the asynchronous task.
	*/
	public static function exec( $function, $priority = 10 ) {
		// Retrieve the singleton instance of Async
		$instance = self::instance();
		$instance->perform( State::PROCESSING ); // Set the state to processing
		$promise = new Promise( $function, $instance ); // Create a promise for the task

		// Enqueue the promise for asynchronous execution
		self::keep( $promise, $priority );

		return $promise;
	}

	/**
	 * Queues a promise for execution.
	 * 
	 * @param Promise $promise The promise representing the asynchronous task.
	 * @param int $priority The priority level for the task.
	*/
	public static function keep( $promise, $priority = 10 ) {
		// Retrieve the singleton instance of Async
		$instance = self::instance();

		// Enqueue the wrapped promise in the task queue with the specified priority
		$instance->tasks()::enqueue( [
			'data' => $instance->wrapPromise( $promise ), 
			'priority' => $priority
		], self::$queueName );
	}

	/**
	 * Wraps a function within a generator to manage execution flow.
	 * 
	 * @param callable $promise The promise to wrap.
	 * @return callable A generator function that yields execution results.
	*/
	protected function wrapPromise( $promise ) {
		// Return a generator function that will execute the wrapped promise
		return function() use ( $promise ) {
			// Execute the promise and yield its results
			$result = $this->executePromise( $promise );
			foreach ( $result as $value ) {
				yield $value;
			}
		};
	}

	/**
	 * Execute the provided promise, intended to be overridden in subclasses for custom behavior.
	 *
	 * @param Promise $promise The promise to execute.
	 * @return \Generator Yields the promise's result, handles success or failure internally.
	*/
	protected function executePromise( $promise ) {
		try {
			// Attempt to execute the promise and yield the result
			$result = $promise->try();
			if ( !( $result instanceof \Generator ) ) {
				yield $result;
			} else {
				yield from $result;
			}
			$this->perform( Event::SUCCESS ); // Trigger success event on successful execution
		} catch ( TransientException $e ) {
			// Handle transient exceptions ( e.g., temporary failures )
			error_log( 'Transient exception: ' . $e->getMessage() );
			$this->perform( Event::ERROR, $e->getMessage() );
			$this->status( $e->getMessage() );

			// Retry the promise if it fails transiently
			$this->retry( $promise->try() );
		} catch ( \Exception $e ) {
			// Handle unhandled exceptions and yield the error message
			yield $this->handleError( 'Unhandled exception: ' . $e );
		}
	}

	/**
	 * Retry the provided function if it fails transiently.
	 * 
	 * @param callable $function The function to retry.
	*/
	protected function retry( $function ) {
		// Retry the provided function by calling it again
		$function();
	}

	/**
	 * Handles errors by logging them and triggering failure events.
	 * 
	 * @param \Exception $e The exception that occurred.
	 * @return null Always returns null after handling the error.
	*/
	protected function handleError( \Exception $e ) {
		// Log the error and trigger the error events
		$this->logError( $e ); // Log the error or perform other error reporting.
		$this->perform( [Event::Error, Event::FAILURE], new Meta( info: $e->getMessage() ) );

		return null;
	}

	/**
	 * Monitors the start of a task for logging and tracking purposes.
	 * 
	 * @param mixed $task The task to monitor.
	*/
	protected function monitorStart( $task ) {
		// Record the start time of the task
		self::$time = time();
	}

	/**
	 * Monitors the end of a task for logging and tracking purposes.
	 * 
	 * @param mixed $task The task to monitor.
	*/
	protected function monitorEnd( $task ) {
		// Calculate and log the duration of the task
		$time = time() - self::$time;
	}

	/**
	 * Logs an error by sending it to the error log.
	 * 
	 * @param \Exception $e The exception to log.
	*/
	protected function logError( \Exception $e ) {
		// Log the error using a logging system or error reporting service.
		error_log( $e );
	}

	/**
	 * Checks if the given task has timed out.
	 * 
	 * @param array $task The task to check.
	 * @throws TimeoutException If the task has timed out.
	*/
	protected function checkTimeout( $task ) {
		// Implement timeout check
		if ( time() - $task['start_time'] > self::getConfig()['task_timeout'] ) {
			throw new TimeoutException( "Task timed out" );
		}
	}

	/**
	 * Sends a notification about the completion of a task.
	 * 
	 * @param array $data The data to send in the notification. This should be an associative array that will be JSON-encoded.
	*/
	protected function notifyCompletion( $data ) {
	// Create a stream context for the HTTP POST request
		$context = stream_context_create( [
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: text/plain",
				'content' => json_encode( $data ) // Encode the data as JSON
			]
		] );

		// Send the HTTP POST request to the notifyURL specified in the configuration
		file_get_contents( self::getConfig()['notifyURL'], false, $context );
	}

	/**
	 * Executes all tasks currently queued for asynchronous processing.
	*/
	public static function run() {
		// Get the singleton instance of the Async class
		$instance = self::instance();

		// Check if the instance is already running
		if ( $instance->is( State::RUNNING ) )
			return; // Exit if already running


   		// Perform start and running events
		$instance->perform( Event::STARTED );
		$instance->perform( State::RUNNING );

		// Process tasks in the queue until it is empty
		while ( !$instance->tasks()::isEmpty( self::$queueName ) ) {
			// Dequeue the next task from the queue
			$task = $instance->tasks()::dequeue( self::$queueName );

			// Start monitoring the task
			$instance->monitorStart( $task );
			$generator = $task();

			// Process the generator yielded by the task
			while ( $generator->valid() ) {
				if ( $generator->current() === null ) {
					// Handle task failure and break out of the loop
					$instance->perform( Event::FAILURE );
					break;
				}
				$generator->next();
			}
			// End monitoring of the task
			$instance->monitorEnd( $task );

			// Perform post-processing and notify completion if needed
			// $instance->notifyCompletion( ['message' => Event::COMPLETE, 'result' => $result] );
			$instance->perform( Event::PROCESSED );
		}

		// Transition to halted state and perform final events
		$instance->halt( State::RUNNING );
		$instance->perform( Event::COMPLETE );
		$instance->perform( Event::STOPPED );
		$instance->halt( State::PROCESSING );
	}

	/**
	 * Destructor method to ensure all tasks are run and resources are cleaned up.
	*/
	public function __destruct() {
		try {
			// Perform the finalizing state transition to indicate cleanup is in progress
			$this->perform( State::FINALIZING );

			// Run all remaining tasks in the queue
			self::run();

			// Mark the finalization process as complete
			$this->perform( Event::FINALIZED );
		} catch ( \Exception $e ) {

			// Handle any exceptions that occur during finalization
			// Log the error and transition to an error state
			$this->perform( Event::ERROR, new Meta( info: $e->getMessage() ) );
			$this->perform( State::ERROR_STATE );
		}

		// Perform the unload event to clean up remaining resources
		$this->perform( Event::UNLOAD );
	}
}