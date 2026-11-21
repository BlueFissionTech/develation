<?php

namespace BlueFission\Tests\IPC;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\IObj;
use BlueFission\IPC\IPC;
use PHPUnit\Framework\TestCase;

class IPCTest extends TestCase
{
    public function testChannelWritesReadsAndClearUseStorageContentsWithoutConsumingReads(): void
    {
        $storage = $this->storage();
        $ipc = new IPC($storage);

        $this->assertSame([], $ipc->read('events'));
        $ipc->write('events', ['number' => 1]);
        $ipc->write('events', ['number' => 2]);
        $ipc->write('other', 'kept');
        $this->assertSame([['number' => 1], ['number' => 2]], $ipc->read('events'));
        $this->assertSame([['number' => 1], ['number' => 2]], $ipc->read('events'));
        $this->assertFalse($storage->is(State::CONNECTED));

        $ipc->clear('events');
        $this->assertSame([], $ipc->read('events'));
        $this->assertSame(['kept'], $ipc->read('other'));
    }

    public function testEmptySerializedListStartsWithNoChannels(): void
    {
        $storage = $this->storage();
        $storage->seed('[]');
        $ipc = new IPC($storage);

        $this->assertSame([], $ipc->read('events'));
        $ipc->write('events', 'first');
        $this->assertSame(['first'], $ipc->read('events'));
    }

    public function testWriteFailureStillDeactivatesOwnedStorage(): void
    {
        $storage = $this->storage();
        $storage->failWrite = true;
        $ipc = new IPC($storage);

        $failure = null;
        try {
            $ipc->write('events', 'value');
        } catch (\RuntimeException $exception) {
            $failure = $exception;
        }

        $this->assertInstanceOf(\RuntimeException::class, $failure);
        $this->assertSame('synthetic write failure', $failure->getMessage());
        $this->assertFalse($storage->is(State::CONNECTED));
    }

    public function testMalformedChannelDataCannotBeSilentlyOverwritten(): void
    {
        $storage = $this->storage();
        $storage->seed(['events' => 'not a message list']);
        $ipc = new IPC($storage);

        $this->expectException(\UnexpectedValueException::class);
        try {
            $ipc->write('events', 'new value');
        } finally {
            $this->assertFalse($storage->is(State::CONNECTED));
        }
    }

    private function storage(): Storage
    {
        return new class extends Storage {
            private mixed $records = [];
            public bool $failWrite = false;

            public function activate(): IObj
            {
                $this->perform(State::CONNECTED);
                return $this;
            }

            public function deactivate(): IObj
            {
                $this->halt(State::CONNECTED);
                return $this;
            }

            public function read(): IObj
            {
                $this->contents($this->records);
                return $this;
            }

            public function write(): IObj
            {
                if ($this->failWrite) {
                    throw new \RuntimeException('synthetic write failure');
                }
                $this->records = $this->contents();
                return $this;
            }

            public function seed(mixed $records): void
            {
                $this->records = $records;
            }
        };
    }
}
