<?php
namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Dispatcher;
 
class DispatcherTest extends \PHPUnit_Framework_TestCase {
 
 	static $classname = 'BlueFission\Behavioral\Dispatcher';
	
	public function setup()
	{
		$this->object = new static::$classname();
	}

	public function testEvaluatesAsStringUsingType()
	{
		$this->assertEquals(static::$classname, "".$this->object."");
	}

	/** 
 	 * @expectedException InvalidArgumentException
 	 */
	public function testThrowsErrorOnUndefinedBehaviorType()
	{
		// var_dump($this->object->testValue);
		$fakeBehavior = new \stdClass();
		$this->object->behavior($fakeBehavior);
	}

	public function testBehaviorsAreDispatched()
	{
		$this->expectOutputString('This Event Was Dispatched');

		$this->object->behavior('testBehavior', function() {
			echo "This Event Was Dispatched";
		});

		$this->object->dispatch('testBehavior');
	}

	/** 
 	 * @expectedException InvalidArgumentException
 	 */
	public function testCantAddEmptyBehaviors()
	{
		$this->object->behavior("");
	}

	public function testBehaviorsTriggerSendsArguments()
	{
		$this->expectOutputString('This Manual Event Was Dispatched');

		$this->object->behavior('testBehavior', function( $data ) {
			echo $data;
		});

		$this->object->dispatch('testBehavior', "This Manual Event Was Dispatched");
	}

	public function testEventFiredOnUnload()
	{
		$this->expectOutputString('This Final Event Was Dispatched');

		$this->object->behavior('OnUnload', function() {
			echo "This Final Event Was Dispatched";
		});

		unset($this->object);
	}

}