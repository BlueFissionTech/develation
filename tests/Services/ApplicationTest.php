<?php
namespace BlueFission\Tests\Services;

use BlueFission\Services\Application;
 
class ApplicationTest extends \PHPUnit\Framework\TestCase {

 	static $classname = 'BlueFission\Services\Application';
 	protected $object;

	public function setUp(): void
	{
		// $this->object = new static::$classname();
		$this->object = (static::$classname)::instance();
	}

	public function tearDown(): void
	{
		$this->object->__destruct();
		$this->object = null;
	}

	public function testAppliationComponentsAreAccessible()
	{

	}

	public function testApplicationDelegatesAreAccessible()
	{

	}

	public function testApplicationCanRouteMessage()
	{
		$this->expectOutputString('Test Output');

		$this->object->register('service1', 'OnEventOne', function($behavior, $args) {
			echo 'Test ';
		});

		$this->object->register('service2', 'DoEventTwo', function($data, $args) {
			echo 'Output';
		});

		$this->object->route('service1', 'service2', 'OnEventOne', 'DoEventTwo');

		$this->object->perform('OnEventOne');
	}

	public function testMessageIsCompleteOnSend()
	{

	}

	public function testMessageIsCompleteOnBoost()
	{

	}

	public function testMessageIsCompleteAfterMultipleRelays()
	{

	}

	public function testServicesMessagesArentGlobal()
	{
		
	}
}