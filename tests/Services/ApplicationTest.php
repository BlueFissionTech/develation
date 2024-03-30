<?php
namespace BlueFission\Tests\Services;

use BlueFission\Services\Application;
 
class ApplicationTest extends \PHPUnit\Framework\TestCase {

 	static $classname = 'BlueFission\Services\Application';

	public function setUp(): void
	{
		// $this->object = new static::$classname();
		$this->object = (static::$classname)::instance();
	}

	public function teardown()
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

		$this->object->register('service1', 'OnEventOne', function() {
			return 'Test Output';
		});

		$this->object->register('service2', 'DoEventTwo', function($data) {
			// echo 'Test Output';
			echo $data->_context;
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