<?php

namespace BlueFission\IPC;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Event;

class IPC {
    protected $storage;
    protected $maxRetries;

    public function __construct(Storage $storage, int $maxRetries = 5) {
        $this->storage = $storage;
        $this->maxRetries = $maxRetries;
    }

    public function write($channel, $message) {
        if ($this->retryConnection()) {
            $data = $this->storage->read() ?? [];
            $data[$channel][] = $message;
            $this->storage->contents($data);
            $this->storage->write();
            $this->storage->deactivate();
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->maxRetries} retries.");
        }
    }

    public function read($channel) {
        if ($this->retryConnection()) {
            $data = $this->storage->read() ?? [];
            $messages = $data[$channel] ?? [];
            $this->storage->deactivate();
            return $messages;
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->maxRetries} retries.");
        }
    }

    public function clear($channel) {
        if ($this->retryConnection()) {
            $data = $this->storage->read() ?? [];
            unset($data[$channel]);
            $this->storage->contents($data);
            $this->storage->write();
            $this->storage->deactivate();
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->maxRetries} retries.");
        }
    }

    protected function retryConnection(): bool {
        $retries = 0;
        while ($retries < $this->maxRetries) {
            $this->storage->activate();
            if ($this->storage->is(State::CONNECTED)) {
                return true;
            }
            $this->storage->deactivate();
            $retries++;
        }
        return false;
    }
}