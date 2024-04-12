<?php
namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Obj;
 
class ConfigurableTest extends BehavioralTest {
 
 	static $classname = 'BlueFission\Behavioral\Configurable';

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
 			'config1'=>'value1',
 			'config2'=>'value2',
 			'config3'=>'value3',
 			'config4'=>'value4',
 		];

 		$this->object->perform(State::DRAFT);

 		$this->object->config($array);

 		$this->assertEquals('value3', $this->object->config('config3'));
 	}

 	public function testFailedAssignmentForNonDraft()
 	{
 		$array = [
 			'config1'=>'value1',
 			'config2'=>'value2',
 			'config3'=>'value3',
 			'config4'=>'value4',
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
 			'config1'=>'value1',
 			'config2'=>'value2',
 			'config3'=>'value3',
 			'config4'=>'value4',
 		];

 		$this->object->config($array);

 		$this->assertEquals('value3', $this->object->config('config3'));

 		$this->object->config('config3', 'new value3');

 		$this->assertEquals('new value3', $this->object->config('config3'));
 	}

 	public function testFailedConfigurationSetOnReadOnly()
 	{
 		$array = [
 			'config1'=>'value1',
 			'config2'=>'value2',
 			'config3'=>'value3',
 			'config4'=>'value4',
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
 			'var1'=>"I'm a variable",
 			'var2'=>"I'm a variable, too",
 			'var3'=>"I'm a variable as well",
 			'var4'=>"Guess what, I'm a variable",
 		];

 		$this->object->assign($array);

 		$this->assertEquals("I'm a variable, too", $this->object->var2 );
 	}

 	public function testDataAssignmentFromArrayFailsWhenReadOnly()
 	{
 		$array = [
 			'var1'=>"I'm a variable",
 			'var2'=>"I'm a variable, too",
 			'var3'=>"I'm a variable as well",
 			'var4'=>"Guess what, I'm a variable",
 		];

 		$this->object->assign($array);

 		$this->assertEquals("I'm a variable, too", $this->object->var2 );

 		$this->object->perform(State::READONLY);

 		$this->object->var2 = "I won't get this new value";

 		$this->assertEquals("I'm a variable, too", $this->object->var2 );
 	}
}