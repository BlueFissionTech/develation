<?php
namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\Behaviors\State;
 
class BehavioralTest extends DispatcherTest {
 
 	static $classname = 'BlueFission\Behavioral\Behaves';

	public function testThrowsErrorOnUndefinedBehaviorPerformance()
	{
		$this->expectException(\InvalidArgumentException::class);
		$fakeBehavior = new \stdClass();
		$this->object->perform($fakeBehavior);
	}

	public function testChecksIfMaintainsState()
	{
		$this->object->perform(State::DRAFT);
		$this->assertTrue($this->object->is(State::DRAFT));
	}

	public function testChecksIfReportsState()
	{
		$this->object->perform(State::DRAFT);
		$this->assertEquals(State::DRAFT, $this->object->is());
	}

	public function testChecksIfCanPerformWhenDraft()
	{
		$this->object->perform(State::DRAFT);
		
		$this->assertTrue($this->object->can('madeupBehavior'));

		$this->object->behavior('madeupBehavior');

		$this->assertTrue($this->object->can('madeupBehavior'));
	}

	public function testChecksIfCanPerformWhenNotDraft()
	{
		$this->object->halt(State::DRAFT);

		$this->assertFalse($this->object->can('madeupBehavior'));

		$this->object->behavior('madeupBehavior');

		$this->assertTrue($this->object->can('madeupBehavior'));
	}
}