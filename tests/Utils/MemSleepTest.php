<?php

namespace BlueFission\Tests\Utils;

use BlueFission\Data\Storage\Storage;
use BlueFission\IObj;
use BlueFission\Utils\Mem;
use PHPUnit\Framework\TestCase;

class MemSleepTest extends TestCase
{
    private array $originalState;

    protected function setUp(): void
    {
        foreach (['pool', 'audit', 'storage'] as $property) {
            $slot = new \ReflectionProperty(Mem::class, $property);
            $this->originalState[$property] = $slot->getValue();
            $slot->setValue($property === 'storage' ? null : []);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalState as $property => $value) {
            (new \ReflectionProperty(Mem::class, $property))->setValue($value);
        }
    }

    public function testSleepWithoutStorageCannotLoseTheOnlyCopy(): void
    {
        $object = (object) ['value' => 'kept'];
        Mem::register($object, 'no-backend');

        Mem::sleep('no-backend');
        Mem::sleepAll();
        Mem::wakeup('no-backend');

        $this->assertSame($object, Mem::get('no-backend'));
        $this->assertSame('kept', Mem::get('no-backend')->value);
    }

    public function testSleepStoresOneSerializationLayerAndWakesAnObject(): void
    {
        $storage = new class extends Storage {
            private array $records = [];

            public function write(): IObj
            {
                $this->records[$this->memory_pool_id] = $this->data;
                $this->status(self::STATUS_SUCCESS);
                return $this;
            }

            public function read(): IObj
            {
                $this->data = $this->records[$this->memory_pool_id] ?? null;
                return $this;
            }

            public function delete(): IObj
            {
                unset($this->records[$this->memory_pool_id]);
                return $this;
            }

            public function stored(string $id): ?string
            {
                return $this->records[$id] ?? null;
            }
        };
        Mem::setStorage($storage);
        Mem::register((object) ['value' => 'round-trip'], 'backed');

        Mem::sleep('backed');
        $this->assertNull(Mem::get('backed'));
        $this->assertInstanceOf(\stdClass::class, unserialize($storage->stored('backed')));

        Mem::wakeup('backed');
        $restored = Mem::get('backed');
        $this->assertInstanceOf(\stdClass::class, $restored);
        $this->assertSame('round-trip', $restored->value);
        $this->assertNull($storage->stored('backed'));
    }

    public function testThrowingStoreLeavesTheRegisteredObjectAvailable(): void
    {
        $storage = new class extends Storage {
            public function write(): IObj
            {
                throw new \RuntimeException('fixture write failure');
            }
        };
        Mem::setStorage($storage);
        $object = (object) ['value' => 'still available'];
        Mem::register($object, 'write-failed');

        try {
            Mem::sleep('write-failed');
            $this->fail('Expected the store failure to remain visible');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fixture write failure', $exception->getMessage());
        }

        $this->assertSame($object, Mem::get('write-failed'));
    }

    public function testFailedOrSilentWriteCannotReuseAnEarlierSuccess(): void
    {
        $storage = new class extends Storage {
            public string $mode = 'success';

            public function write(): IObj
            {
                if ($this->mode === 'success') {
                    $this->status(self::STATUS_SUCCESS);
                } elseif ($this->mode === 'failed') {
                    $this->status(self::STATUS_FAILED);
                }
                return $this;
            }
        };
        Mem::setStorage($storage);
        Mem::register((object) ['value' => 'first'], 'first');
        Mem::sleep('first');
        $this->assertNull(Mem::get('first'));

        foreach (['failed', 'silent'] as $mode) {
            $storage->mode = $mode;
            $object = (object) ['value' => 'still available'];
            Mem::register($object, $mode);
            $failure = null;
            try {
                Mem::sleep($mode);
            } catch (\RuntimeException $exception) {
                $failure = $exception;
            }
            $this->assertInstanceOf(\RuntimeException::class, $failure);
            $this->assertSame('Memory object could not be stored.', $failure->getMessage());
            $this->assertSame($object, Mem::get($mode));
        }
    }

    public function testWriteWithoutFreshSuccessCannotDiscardTheObject(): void
    {
        $storage = new class extends Storage {
            public function write(): IObj
            {
                return $this;
            }
        };
        Mem::setStorage($storage);
        $object = (object) ['value' => 'unconfirmed'];
        Mem::register($object, 'unconfirmed');

        $failure = null;
        try {
            Mem::sleep('unconfirmed');
        } catch (\RuntimeException $exception) {
            $failure = $exception;
        }
        $this->assertInstanceOf(\RuntimeException::class, $failure);
        $this->assertSame('Memory object could not be stored.', $failure->getMessage());
        $this->assertSame($object, Mem::get('unconfirmed'));
    }
}
