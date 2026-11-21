<?php

namespace BlueFission\IPC;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Arr;

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
            try {
                $data = $this->readData();
                $messages = $data[$channel] ?? [];
                if (!Arr::is($messages)) {
                    throw new \UnexpectedValueException('IPC channel contents must be an array.');
                }
                $messages[] = $message;
                $data[$channel] = $messages;
                $this->_storage->contents($data);
                $this->_storage->write();
            } finally {
                $this->_storage->deactivate();
            }
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->_maxRetries} retries.");
        }
    }

    public function read(string $channel): array
    {
        if ($this->retryConnection()) {
            try {
                $data = $this->readData();
                $messages = $data[$channel] ?? [];
                if (!Arr::is($messages)) {
                    throw new \UnexpectedValueException('IPC channel contents must be an array.');
                }
                return $messages;
            } finally {
                $this->_storage->deactivate();
            }
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->_maxRetries} retries.");
        }
    }

    public function clear(string $channel): void
    {
        if ($this->retryConnection()) {
            try {
                $data = $this->readData();
                unset($data[$channel]);
                $this->_storage->contents($data);
                $this->_storage->write();
            } finally {
                $this->_storage->deactivate();
            }
        } else {
            throw new \RuntimeException("Failed to connect to storage after {$this->_maxRetries} retries.");
        }
    }

    private function readData(): array
    {
        // Storage::read() is fluent and returns the Storage object, not data.
        $this->_storage->read();
        $data = $this->_storage->contents();
        if ($data === null || $data === '' || $data === '[]') {
            return [];
        }
        if (!Arr::is($data)) {
            throw new \UnexpectedValueException('IPC storage contents must be an array.');
        }
        return $data;
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
