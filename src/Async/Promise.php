<?php

namespace BlueFission\Async;

use BlueFission\Func;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Promise
 *
 * Represents a deferred computation that can be fulfilled or rejected.
 * Based on JavaScript-like promise behavior with resolve/reject callbacks.
 */
class Promise
{
    protected $_action;
    protected ?Func $_onFulfill = null;
    protected ?Func $_onReject = null;
    protected mixed $_result = null;
    protected string $_state = State::PENDING;
    protected mixed $_asyncInstance;

    /**
     * Promise constructor.
     *
     * @param callable $action The async action that takes resolve/reject callbacks.
     * @param mixed|null $asyncInstance An optional Async instance (used for triggering events).
     */
    public function __construct(callable $action, $asyncInstance = null)
    {
        $this->_action = new Func($action);
        $this->_asyncInstance = $asyncInstance;
    }

    /**
     * Executes the stored async function.
     *
     * @return void
     */
    public function try(): void
    {
        try {
            ($this->_action)($this->resolve(), $this->reject());
        } catch (\Exception $e) {
            $this->reject()($e);
        }
    }

    /**
     * Attach success and failure handlers.
     *
     * @param callable $onFulfill Function called when the promise is fulfilled.
     * @param callable|null $onReject Function called if the promise is rejected.
     * @return $this
     */
    public function then(callable $onFulfill, callable $onReject = null): self
    {
        $this->_onFulfill = new Func($onFulfill);
        $this->_onReject = $onReject ? new Func($onReject) : null;
        return $this;
    }

    /**
     * Magic getter for accessing internal properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if ($name === 'async') {
            return $this->_asyncInstance;
        }

        return null;
    }

    /**
     * Internal resolve function returned to the executor.
     *
     * @return callable
     */
    protected function resolve(): callable
    {
        return function ($value = null) {
            if ($this->_state === State::PENDING) {
                $this->_state = State::FULFILLED;
                $this->_result = $value;

                if ($this->_onFulfill && $this->_onFulfill->isCallable()) {
                    $this->_onFulfill->call($this->_result);
                }

                if ($this->_asyncInstance instanceof IAsync) {
                    $this->_asyncInstance->perform(Event::SUCCESS, new Meta(data: $this));
                }
            }
        };
    }

    /**
     * Internal reject function returned to the executor.
     *
     * @return callable
     */
    protected function reject(): callable
    {
        return function ($reason = null) {
            if ($this->_state === State::PENDING) {
                $this->_state = State::REJECTED;
                $this->_result = $reason;

                if ($this->_onReject && $this->_onReject->isCallable()) {
                    $this->_onReject->call($this->_result);
                }

                if ($this->_asyncInstance instanceof IAsync) {
                    $this->_asyncInstance->perform(Event::FAILURE, new Meta(data: $this));
                }
            }
        };
    }
}
