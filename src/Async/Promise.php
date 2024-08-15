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

    public function __construct(callable $_action, $_asyncInstance = null) {
        $this->_action = new Func($_action);
        $this->_asyncInstance = $_asyncInstance;
        // $this->start();
    }

    public function try() {
        try {
            ($this->_action)($this->resolve(), $this->reject());
        } catch (\Exception $_e) {
            $this->reject()($_e);
        }
    }

    public function then(callable $_onFulfill, callable $_onReject = null) {
        $this->_onFulfill = new Func($_onFulfill);
        $this->_onReject = new Func($_onReject);
        return $this;
    }

    public function __get($_name) {
        if ($_name == 'async') {
            return $this->_asyncInstance;
        }
    }

    protected function resolve() {
        return function ($_value = null) {
            if ($this->_state === State::PENDING) {
                $this->_state = State::FULFILLED;
                $this->_result = $_value;
                if ($this->_onFulfill->isCallable()) {
                    $this->_onFulfill->call($this->_result);
                }
                if ($this->_asyncInstance && is_a(IAsync::class, $this->_asyncInstance)) {
                    $this->_asyncInstance->perform(Event::SUCCESS, new Meta(data: $this));
                }
            }
        };
    }

    protected function reject() {
        return function ($_reason = null) {
            if ($this->_state === State::PENDING) {
                $this->_state = State::REJECTED;
                $this->_result = $_reason;
                if ($this->_onReject->isCallable()) {
                    $this->_onReject->call($this->_result);
                }
                if ($this->_asyncInstance && is_a(IAsync::class, $this->_asyncInstance)) {
                    $this->_asyncInstance->perform(Event::FAILURE, new Meta(data: $this));
                }
            }
        };
    }
}
