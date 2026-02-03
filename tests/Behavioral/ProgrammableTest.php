<?php

namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Programmable;
use BlueFission\Obj;

class ProgrammableTest extends ConfigurableTest
{
    public static $classname = 'BlueFission\Behavioral\Programmable';

    public function setUp(): void
    {
        $traitName = static::$classname;
        $this->object = eval("
            return new class extends BlueFission\\Obj implements BlueFission\\Behavioral\\IDispatcher {
                use BlueFission\\Behavioral\\Configurable;
                use $traitName;

                protected \$_config = [];

                public function __construct()
                {
                    parent::__construct();
                    \$this->bootstrapConfig();
                    \$this->bootstrapProgrammable();
                }
            };
        ");
    }

    public function testAddMethodWithLearn()
    {
        $this->expectOutputString('This is a test');

        $this->object->learn('testMethod', function () {
            echo "This is a test";
        });

        $this->object->testMethod();
    }

    public function testAddMethodAsProperty()
    {
        $this->expectOutputString('This is another test');

        $this->object->testMethod = function () {
            echo "This is another test";
        };

        $this->object->testMethod();
    }

    public function testLearnedMethodAccessToMemberVariables()
    {
        $this->expectOutputString('Yet another test');

        $this->object->testProperty = "Yet another test";

        $this->object->testMethod = function () {
            echo $this->testProperty;
        };

        $this->object->testMethod();
    }

    public function testLearnedMethodAccessToProtectedVariables()
    {
        $this->object->var1 = 1;
        $this->object->var2 = 2;
        $this->object->var3 = 3;


        $this->object->testMethod = function () {
            return count($this->_data);
        };

        $number = $this->object->testMethod();

        $this->assertEquals(3, $number);
    }

    public function testClassCanForgetMethods()
    {
        $this->expectOutputString('Yet another test');

        $this->object->testProperty = "Yet another test";

        $this->object->testMethod = function () {
            echo $this->testProperty;
        };

        $this->object->testMethod();

        $this->object->forget('testMethod');

        $this->expectException(\RuntimeException::class);
        $this->object->testMethod();
    }
}
