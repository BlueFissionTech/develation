<?php

namespace BlueFission\Async;

use BlueFission\Connections\Curl;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Remote
 * 
 * This class extends the Async class to handle asynchronous HTTP requests using the Curl class.
 * It provides an interface to send HTTP requests asynchronously and manage the responses using events.
*/
class Remote extends Async {

    /**
	 * Executes an HTTP request using the Curl class.
	 * 
	 * This method constructs a Curl request, binds event handlers for connection, processing,
	 * failure, and error events, and executes the request asynchronously.
	 * 
	 * @param string $url The URL to send the HTTP request to.
	 * @param array $options An associative array of options, including method, headers, data, and credentials.
	 * @param int $priority The priority of the task in the queue, with higher values processed earlier.
	 * @return Remote The instance of the Remote class, allowing method chaining.
	*/
    public static function do( $url, array $options = [], $priority = 10 ) {
        // Define the function to handle the HTTP request
        $function = function( $resolve, $reject ) use ( $url, $options ) {
            $result = null; // Variable to store the result of the HTTP request

            // Initialize Curl with the provided URL and options ( method, headers, credentials )
            $curl = new Curl( [
                'target' => $url,
                'method' => $options['method'] ?? 'get', // Default method is GET if not provided
                'headers' => $options['headers'] ?? [], // Default to empty headers
                'username' => $options['username'] ?? null, // Optional username for authentication
                'password' => $options['password'] ?? null  // Optional password for authentication
            ] );

            // Assign data to the Curl request if provided in options
            if ( !empty( $options['data'] ) ) {
                $curl->assign( $options['data'] );
            }

            // Set event handlers for Curl actions
            $curl
            ->when( Event::CONNECTED, function( $behavior, $args ) use ( $curl ) {
                $curl->query(); // When connected, send the request
            } )
            ->when( Event::PROCESSED, function( $behavior, $args ) use ( $resolve, $curl, &$result ) {
                // On successful processing, retrieve the result and resolve the promise
                $result = $curl->result();
                $curl->close(); // Close the Curl connection
                $resolve( $result ); // Resolve the promise with the result
            } )
            ->when( Event::FAILURE, ( function( $behavior, $args ) use ( $reject ) {
                // Handle failures by rejecting the promise and throwing an exception
                $reject( $args->info ); // Reject with the failure information
                $httpStatusCode = ( $this->connection ? curl_getinfo( $this->connection, CURLINFO_HTTP_CODE ) : 'No Connection' );

                throw new \Exception( "HTTP request failed: ( {$httpStatusCode} ) " . $args->info );
            } )->bindTo( $curl, $curl ) ) // Bind the failure handler to the Curl instance
            
            ->when( Event::ERROR, ( function( $behavior, $args ) use ( $reject ) {
                // Handle errors by rejecting the promise and throwing an exception
                $reject( $args->info ); // Reject with the error information
                $httpStatusCode = curl_getinfo( $this->connection, CURLINFO_HTTP_CODE ); // Get HTTP status code

                throw new \Exception( "HTTP request error: ( {$httpStatusCode} ) " . $args->info );
            } )->bindTo( $curl, $curl ) ) // Bind the error handler to the Curl instance
            ->open(); // Open the Curl connection to start the request

            // If the result is empty, throw an exception
            if ( !$result ) {
                throw new \Exception( "HTTP response empty: " . $curl->status() );
            }
        };

        // Execute the function using the parent class's exec method and return the Remote instance
        return static::exec( $function, $priority );
    }

    /**
	 * Overrides the executeFunction method from Async to handle HTTP-specific retries and errors.
	 * 
	 * @param callable $function The asynchronous function to be executed.
	 * @return void
	*/
    protected function executeFunction( $function ) {
        try {
            // Execute the function and yield the result
            $result = $function();
            yield $result;
            // Trigger the SUCCESS event upon successful execution
            $this->perform( Event::SUCCESS );
        } catch ( \Exception $e ) {
            // Trigger the FAILURE event if an exception is caught
            $this->perform( Event::FAILURE, ['message' => $e->getMessage()] );
            // Log the error using the parent class's logError method
            $this->logError( $e );
            // Retry the function if the exception is retryable
            if ( $this->shouldRetry( $e ) ) {
                $this->retry( $function );
            } else {
                // Yield null if the failure is non-retryable
                yield null; // Yield null on non-retryable failure
            }
        }
    }

    /**
	 * Determines whether the request should be retried based on the exception message.
	 * 
	 * @param \Exception $e The exception thrown during the request.
	 * @return bool True if the request should be retried, false otherwise.
	*/
    protected function shouldRetry( \Exception $e ) {
        // Flag to indicate whether the request should be retried
        $retry = false;

        // Retry on timeout errors
        if ( strpos( $e->getMessage(), 'timed out' ) !== false ) {
            $retry = true;
        }

        // Retry on HTTP 500 errors ( internal server errors )
        if ( strpos( $e->getMessage(), '( 500 )' ) !== false ) {
            $retry = true;
        }

        return $retry;
    }

    /**
	 * Logs the error, optionally to a log file or an error tracking service.
	 * 
	 * @param \Exception $e The exception to log.
	 * @return void
	*/
    protected function logError( \Exception $e ) {
        // Call the parent class's logError method to log the error
        parent::logError( $e );
    }
}