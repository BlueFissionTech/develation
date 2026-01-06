<?php

namespace BlueFission\Async;

use BlueFission\Func;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

class Promise {
    protected $_action;
    protected $_onFulfill = null;
    protected $_onReject = null;
    protected $_result = null;
    protected $_state = State::PENDING;
    protected $_asyncInstance;

    public function __construct(callable $action, $asyncInstance = null) {
        $this->_action = new Func($action);
        $this->_asyncInstance = $asyncInstance;
        // $this->start();
    }

    public function try() {
        try {
            ($this->_action)($this->resolve(), $this->reject());
        } catch (\Exception $e) {
            $this->reject()($e);
        }
    }

    public function then(callable $onFulfill, callable $onReject = null) {
        $this->_onFulfill = new Func($onFulfill);
        $this->_onReject = new Func($onReject);
        return $this;
    }

    public function __get($name) {
        if ($name == 'async') {
            return $this->_asyncInstance;
        }
    }

    protected function resolve() {
        return function ($value = null) {
            if ($this->_state === State::PENDING) {
                $this->_state = State::FULFILLED;
                $this->_result = $value;
                
                if ($this->_onFulfill && $this->_onFulfill->isCallable()) {
                    $this->_onFulfill->call($this->_result);
                }
                if ($this->_asyncInstance && is_a($this->_asyncInstance, IAsync::class)) {
                    $this->_asyncInstance->perform(Event::SUCCESS, new Meta(data: $this));
                }
            }
        };
    }

    protected function reject() {
        return function ($reason = null) {
            if ($this->_state === State::PENDING) {
                $this->_state = State::REJECTED;
                $this->_result = $reason;
                if ($this->_onReject->isCallable()) {
                    $this->_onReject->call($this->_result);
                }
                if ($this->_asyncInstance && is_a($this->_asyncInstance, IAsync::class)) {
                    $this->_asyncInstance->perform(Event::FAILURE, new Meta(data: $this));
                }
            }
        };
    }
}
