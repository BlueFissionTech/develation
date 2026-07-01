<?php

namespace BlueFission\IPC;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\State;

class IPC implements IIPC
{
    protected $_storage;
    protected $_maxRetries;

    public function __construct(Storage $storage, int $maxRetries = 5)
    {
        $this->_storage = $storage;
        $this->_maxRetries = $maxRetries;
    }

    public function write(string $channel, mixed $message): void
    {
        if ($this->retryConnection()) {
            $data = $this->_storage->read() ?? [];
            $data[$channel][] = $message;
            $this->_storage->contents($data);
            $this->_storage->write();
            $this->_storage->deactivate();
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->_maxRetries} retries.");
        }
    }

    public function read(string $channel): array
    {
        if ($this->retryConnection()) {
            $data = $this->_storage->read() ?? [];
            $messages = $data[$channel] ?? [];
            $this->_storage->deactivate();
            return $messages;
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->_maxRetries} retries.");
        }
    }

    public function clear(string $channel): void
    {
        if ($this->retryConnection()) {
            $data = $this->_storage->read() ?? [];
            unset($data[$channel]);
            $this->_storage->contents($data);
            $this->_storage->write();
            $this->_storage->deactivate();
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->_maxRetries} retries.");
        }
    }

    protected function retryConnection(): bool
    {
        $retries = 0;
        while ($retries < $this->_maxRetries) {
            $this->_storage->activate();
            if ($this->_storage->is(State::CONNECTED)) {
                return true;
            }
            $this->_storage->deactivate();
            $retries++;
        }
        return false;
    }
}
