<?php

namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IPCDispatches;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\IPC\IIPC;

class DispatcherTest extends \PHPUnit\Framework\TestCase
{
    public static $classname = 'BlueFission\Behavioral\Dispatches';
    protected $object;

    public function setUp(): void
    {
        $traitName = static::$classname;
        $this->object = eval("
	        return new class implements BlueFission\Behavioral\IDispatcher {
	            use $traitName;
	        };
	    ");
    }


    public function testThrowsErrorOnUndefinedBehaviorType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $fakeBehavior = new \stdClass();
        $this->object->behavior($fakeBehavior);
    }

    public function testBehaviorsAreDispatched()
    {
        $this->expectOutputString('This Event Was Dispatched');

        $this->object->behavior('testBehavior', function () {
            echo "This Event Was Dispatched";
        });

        $this->object->dispatch('testBehavior');
    }

    public function testCantAddEmptyBehaviors()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->object->behavior("");
    }

    public function testBehaviorsTriggerSendsArguments()
    {
        $this->expectOutputString('This Manual Event Was Dispatched');

        $this->object->behavior('testBehavior', function ($behavior, $data) {
            echo $data[0];
        });

        $this->object->dispatch('testBehavior', "This Manual Event Was Dispatched");
    }

    public function testDispatchMirrorsToInjectedIPC()
    {
        $ipc = new class implements IIPC {
            public array $messages = [];

            public function write(string $channel, mixed $message): void
            {
                $this->messages[$channel][] = $message;
            }

            public function read(string $channel): array
            {
                return $this->messages[$channel] ?? [];
            }

            public function clear(string $channel): void
            {
                unset($this->messages[$channel]);
            }
        };

        $this->expectOutputString('local');

        $this->object->setIPC($ipc);
        $this->object->behavior('testBehavior', function () {
            echo 'local';
        });
        $this->object->dispatch('testBehavior', 'payload');

        $this->assertSame(
            [
                [
                    'behavior' => 'testBehavior',
                    'args' => ['payload'],
                ],
            ],
            $ipc->messages['testBehavior']
        );
    }

    public function testIPCDispatchesTraitHelpersUseTransport()
    {
        $ipc = new class implements IIPC {
            public array $messages = [];

            public function write(string $channel, mixed $message): void
            {
                $this->messages[$channel][] = $message;
            }

            public function read(string $channel): array
            {
                return $this->messages[$channel] ?? [];
            }

            public function clear(string $channel): void
            {
                unset($this->messages[$channel]);
            }
        };

        $object = new class {
            use IPCDispatches;
        };
        $received = [];

        $object
            ->setIPC($ipc)
            ->dispatchIPC('manual', ['value' => 1])
            ->listenIPC('manual', function ($message) use (&$received) {
                $received[] = $message;
            });

        $this->assertSame([['value' => 1]], $received);
        $this->assertSame([], $ipc->read('manual'));
    }
}
