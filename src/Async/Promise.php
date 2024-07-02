<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

class Promise {
    protected $action;
    protected $onFulfill = null;
    protected $onReject = null;
    protected $result = null;
    protected $state = State::PENDING;
    protected $asyncInstance;

    public function __construct(callable $action, $asyncInstance = null) {
        $this->action = $action;
        $this->asyncInstance = $asyncInstance;
        // $this->start();
    }

    public function try() {
        try {
            ($this->action)($this->resolve(), $this->reject());
        } catch (\Exception $e) {
            $this->reject()($e);
        }
    }

    public function then(callable $onFulfill, callable $onReject = null) {
        $this->onFulfill = $onFulfill;
        $this->onReject = $onReject;
        return $this;
    }

    public function __get($name) {
        if ($name == 'async') {
            return $this->asyncInstance;
        }
    }

    protected function resolve() {
        return function ($value = null) {
            if ($this->state === State::PENDING) {
                $this->state = State::FULFILLED;
                $this->result = $value;
                if ($this->onFulfill) {
                    call_user_func($this->onFulfill, $this->result);
                }
                if ($this->asyncInstance && is_a(IAsync::class, $this->asyncInstance)) {
                    $this->asyncInstance->perform(Event::SUCCESS, new Meta(data: $this));
                }
            }
        };
    }

    protected function reject() {
        return function ($reason = null) {
            if ($this->state === State::PENDING) {
                $this->state = State::REJECTED;
                $this->result = $reason;
                if ($this->onReject) {
                    call_user_func($this->onReject, $this->result);
                }
                if ($this->asyncInstance && is_a(IAsync::class, $this->asyncInstance)) {
                    $this->asyncInstance->perform(Event::FAILURE, new Meta(data: $this));
                }
            }
        };
    }
}
