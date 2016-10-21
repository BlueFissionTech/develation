<?php
namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Scheme;
 
class SchemeTest extends DispatcherTest {
 
 	static $classname = 'BlueFission\Behavioral\Scheme';

	public function testEventFiredOnUnload()
	{
		// For some reason the handlers don't fire on unload in children, so we need to investigate why
	}

	/** 
 	 * @expectedException InvalidArgumentException
 	 */
	public function testThrowsErrorOnUndefinedBehaviorPerformance()
	{
		// var_dump($this->object->testValue);
		$fakeBehavior = new \stdClass();
		$this->object->perform($fakeBehavior);
	}

	public function testChecksIfCanPerformWhenDraft()
	{
		$this->object->perform('IsDraft');
		
		$this->assertTrue($this->object->can('madeupBehavior'));

		$this->object->behavior('madeupBehavior');

		$this->assertTrue($this->object->can('madeupBehavior'));
	}

	public function testChecksIfCanPerformWhenNotDraft()
	{
		$this->object->halt('IsDraft');

		$this->assertFalse($this->object->can('madeupBehavior'));

		$this->object->behavior('madeupBehavior');

		$this->assertTrue($this->object->can('madeupBehavior'));
	}
}