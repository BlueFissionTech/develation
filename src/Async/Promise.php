<?php

namespace BlueFission\Async;

use BlueFission\Func;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Promise represents an asynchronous operation.
 * It manages the state of the operation and handles success and failure callbacks.
 */
class Promise {
    /**
	 * @var Func Callable function to execute the asynchronous operation.
	*/
    protected $action;

    /**
	 * @var Func|null Callback to be executed when the promise is fulfilled.
	*/
    protected $onFulfill = null;

    /**
	 * @var Func|null Callback to be executed when the promise is rejected.
	*/
    protected $onReject = null;

    /**
	 * @var mixed Result of the asynchronous operation.
	*/
    protected $result = null;

    /**
	 * @var string The current state of the promise. Can be PENDING, FULFILLED, or REJECTED.
	*/
    protected $state = State::PENDING;

    /**
	 * @var IAsync|null The instance of the asynchronous handler.
	*/
    protected $asyncInstance;

    /**
	 * Constructor to initialize the promise.
	 *
	 * @param callable $action The function that represents the asynchronous operation.
	 * @param IAsync|null $asyncInstance The instance of the asynchronous handler, if any.
	*/
    public function __construct( callable $action, $asyncInstance = null ) {
        $this->action = new Func( $action ); // Wrap the callable action
        $this->asyncInstance = $asyncInstance; // Store the async instance if provided
        // Optionally start the promise immediately
        // $this->start();
    }

    /**
	 * Executes the promise action and handles exceptions.
	 *
	 * This method invokes the action with resolve and reject functions. If an exception occurs,
	 * the reject function is called with the exception.
	 *
	 * @return void
	*/
    public function try() {
        try {
            ( $this->action )( $this->resolve(), $this->reject() );
        } catch ( \Exception $e ) {
            $this->reject()( $e ); // Reject the promise if an exception is thrown
        }
    }

    /**
	 * Sets up the callbacks for when the promise is fulfilled or rejected.
	 *
	 * @param callable $onFulfill The callback to execute when the promise is fulfilled.
	 * @param callable|null $onReject The callback to execute when the promise is rejected.
	 * @return $this The current instance of the Promise for method chaining.
	*/
    public function then( callable $onFulfill, callable $onReject = null ) {
        $this->onFulfill = new Func( $onFulfill ); // Set the fulfill callback
        $this->onReject = new Func( $onReject ); // Set the reject callback
        return $this; // Return the current instance for chaining
    }

    /**
	 * Magic method to access properties dynamically.
	 *
	 * @param string $name The name of the property to access.
	 * @return mixed The value of the property, if accessible.
	*/
    public function __get( $name ) {
        if ( $name == 'async' ) {
            return $this->asyncInstance; // Return the async instance if requested
        }
    }

    /**
	 * Returns a closure that resolves the promise.
	 *
	 * This method changes the state to FULFILLED, sets the result, and invokes the fulfill callback.
	 * It also performs any asynchronous operations if an async instance is provided.
	 *
	 * @return callable The closure to resolve the promise.
	*/
    protected function resolve() {
        return function ( $value = null ) {
            if ( $this->state === State::PENDING ) {
                $this->state = State::FULFILLED; // Update the state to FULFILLED
                $this->result = $value; // Set the result
                if ( $this->onFulfill->isCallable() ) {
                    $this->onFulfill->call( $this->result ); // Call the fulfill callback
                }
                if ( $this->asyncInstance && is_a( IAsync::class, $this->asyncInstance ) ) {
                    $this->asyncInstance->perform( Event::SUCCESS, new Meta( data: $this ) ); // Perform async success event
                }
            }
        };
    }

    /**
	 * Returns a closure that rejects the promise.
	 *
	 * This method changes the state to REJECTED, sets the result, and invokes the reject callback.
	 * It also performs any asynchronous operations if an async instance is provided.
	 *
	 * @return callable The closure to reject the promise.
	*/
    protected function reject() {
        return function ( $reason = null ) {
            if ( $this->state === State::PENDING ) {
                $this->state = State::REJECTED; // Update the state to REJECTED
                $this->result = $reason; // Set the result as the rejection reason
                if ( $this->onReject->isCallable() ) {
                    $this->onReject->call( $this->result ); // Call the reject callback
                }
                if ( $this->asyncInstance && is_a( IAsync::class, $this->asyncInstance ) ) {
                    $this->asyncInstance->perform( Event::FAILURE, new Meta( data: $this ) ); // Perform async failure event
                }
            }
        };
    }
}