<?php

namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IDispatcher;

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

}
