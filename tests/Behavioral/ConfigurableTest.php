<?php

namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\DataTypes;
use BlueFission\Obj;

class ConfigurableTest extends BehavioralTest
{
    public static $classname = 'BlueFission\Behavioral\Configurable';

    public function setUp(): void
    {
        $traitName = static::$classname;
        $this->object = eval("
	        return new class extends BlueFission\Obj implements BlueFission\Behavioral\IDispatcher {
	            use $traitName;

	            protected \$_config = [];
	        };
	    ");
    }

    public function testAssocArrayAssignment()
    {
        $array = [
            'config1' => 'value1',
            'config2' => 'value2',
            'config3' => 'value3',
            'config4' => 'value4',
        ];

        $this->object->perform(State::DRAFT);

        $this->object->config($array);

        $this->assertEquals('value3', $this->object->config('config3'));
    }

    public function testFailedAssignmentForNonDraft()
    {
        $array = [
            'config1' => 'value1',
            'config2' => 'value2',
            'config3' => 'value3',
            'config4' => 'value4',
        ];

        $this->object->halt(State::DRAFT);

        $this->object->config($array);

        $this->assertEquals(null, $this->object->config('config3'));
    }

    public function testConfigurationDirectAddition()
    {
        $this->object->config('config5', 'value5');

        $this->assertEquals('value5', $this->object->config('config5'));
    }

    public function testConfigurationChange()
    {
        $array = [
            'config1' => 'value1',
            'config2' => 'value2',
            'config3' => 'value3',
            'config4' => 'value4',
        ];

        $this->object->config($array);

        $this->assertEquals('value3', $this->object->config('config3'));

        $this->object->config('config3', 'new value3');

        $this->assertEquals('new value3', $this->object->config('config3'));
    }

    public function testFailedConfigurationSetOnReadOnly()
    {
        $array = [
            'config1' => 'value1',
            'config2' => 'value2',
            'config3' => 'value3',
            'config4' => 'value4',
        ];

        $this->object->config($array);

        $this->assertEquals('value3', $this->object->config('config3'));

        $this->object->perform(State::READONLY);

        $this->object->config('config3', 'new value3');

        $this->assertEquals('value3', $this->object->config('config3'));
    }

    public function testDataAssignmentFromArray()
    {
        $array = [
            'var1' => "I'm a variable",
            'var2' => "I'm a variable, too",
            'var3' => "I'm a variable as well",
            'var4' => "Guess what, I'm a variable",
        ];

        $this->object->assign($array);

        $this->assertEquals("I'm a variable, too", $this->object->var2);
    }

    public function testDataAssignmentFromArrayFailsWhenReadOnly()
    {
        $array = [
            'var1' => "I'm a variable",
            'var2' => "I'm a variable, too",
            'var3' => "I'm a variable as well",
            'var4' => "Guess what, I'm a variable",
        ];

        $this->object->assign($array);

        $this->assertEquals("I'm a variable, too", $this->object->var2);

        $this->object->perform(State::READONLY);

        $this->object->var2 = "I won't get this new value";

        $this->assertEquals("I'm a variable, too", $this->object->var2);
    }

    public function testDraftFieldsStoreFalsyValues()
    {
        $this->object->perform(State::DRAFT);

        foreach ([false, null, 0, '', []] as $index => $value) {
            $field = 'value' . $index;
            $this->assertSame($this->object, $this->object->field($field, $value));
            $this->assertSame($value, $this->object->field($field));
        }
    }

    public function testTypedFieldsStoreFalsyValuesOutsideDraftState()
    {
        $object = new class extends Obj implements \BlueFission\Behavioral\IDispatcher {
            use Configurable;

            protected $_config = [];
            protected $_types = [
                'flag' => DataTypes::BOOLEAN,
                'count' => DataTypes::NUMBER,
                'name' => DataTypes::STRING,
                'items' => DataTypes::ARRAY,
                'optional' => DataTypes::GENERIC,
            ];
        };

        $object->field('optional', 'present');
        foreach ([
            'flag' => false,
            'count' => 0,
            'name' => '',
            'items' => [],
            'optional' => null,
        ] as $field => $value) {
            $this->assertSame($object, $object->field($field, $value));
            $this->assertSame($value, $object->field($field));
        }
    }

    public function testReadonlyFieldSetterRemainsFluentWithoutChangingValue()
    {
        $this->object->perform(State::DRAFT);
        $this->object->field('enabled', true);
        $this->object->perform(State::READONLY);

        $this->assertSame($this->object, $this->object->field('enabled', false));
        $this->assertTrue($this->object->field('enabled'));
    }
}
