<?php
namespace BlueFission\Tests\Services;

use BlueFission\Services\Service;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Handler;
 
class ServiceTest extends \PHPUnit\Framework\TestCase {

 	static $classname = 'BlueFission\Services\Service';

	public function setup()
	{
		$this->object = new static::$classname();
	}

	    public function testServiceInstance()
    {
        $service = new Service();
        $this->assertInstanceOf(Service::class, $service);
    }

    public function testNameMethod()
    {
        $service = new Service();
        $this->assertInternalType('string', $service->name());
    }

    public function testParentMethod()
    {
        $service = new Service();
        $parent = new Service();
        $service->parent($parent);
        $this->assertInstanceOf(Service::class, $service->parent());
    }

    public function testBroadcastMethod()
    {
        $service = new Service();
        $behavior = $this->getMockBuilder(Behavior::class)->getMock();
        $service->broadcast($behavior);
        $this->assertAttributeInstanceOf(Behavior::class, '_target', $behavior);
    }

    public function testBoostMethod()
    {
        $service = new Service();
        $parent = $this->getMockBuilder(\BlueFission\Services\Application::class)->getMock();
        $service->parent($parent);
        $behavior = $this->getMockBuilder(Behavior::class)->getMock();
        $service->boost($behavior);
        $this->assertAttributeInstanceOf(Behavior::class, '_target', $behavior);
    }

    public function testMessageMethod()
    {
        $service = new Service();
        $instance = $this->getMockBuilder(Dispatcher::class)->getMock();
        $behavior = $this->getMockBuilder(Behavior::class)->getMock();
        $instance->method('behavior')->willReturn(true);
        $instance->method('dispatch')->with($behavior, null);
        $service->instance = $instance;
        $service->message('behavior');
    }

	public function testServicesCanDispatchLocalizedEvents()
	{
		$this->expectOutputString('Test message 1');

		$this->object->type = '\Bluefission\Behavioral\Configurable';

		$behavior = new Behavior('Test behavior');

		$handler = new Handler($behavior, function() {
			echo "Test message 1";
		});

		$this->object->register('testService', $handler, Service::LOCAL_LEVEL);

		$this->object->message('Test behavior');
	}

	public function testServicesCanDispatchScopedEvents()
	{
		$this->expectOutputString('Test message 2');

		$this->object->type = '\Bluefission\Behavioral\Configurable';

		$behavior = new Behavior('Test behavior');

		$handler = new Handler($behavior, function() {
			echo "Test message 2";
		});

		$this->object->register('testService', $handler, Service::SCOPE_LEVEL);

		$this->object->message('Test behavior');
	}

	public function testServicesUseLocalPropertiesOnDispatch()
	{
		$this->expectOutputString('foo');

		$this->object->type = '\Bluefission\Behavioral\Configurable';

		$behavior = new Behavior('Test behavior');

		$this->object->scope = $this;

		$this->test_var = "foo";

		$this->object->test_var = "bar";

		$handler = new Handler($behavior, function( $data ) {
			echo $this->test_var;			
		});

		$this->object->register('testService', $handler);

		$this->object->message('Test behavior');
	}

	public function testServicesUseScopedPropertiesOnDispatch()
	{
		$this->expectOutputString('bar');

		$this->object->type = '\Bluefission\Behavioral\Configurable';

		$behavior = new Behavior('Test behavior');

		$this->test_var = "foo";

		$this->object->test_var = "bar";

		$handler = new Handler($behavior, function( $data ) {
			echo $this->test_var;
		});

		$this->object->register('testService', $handler);

		$this->object->message('Test behavior');
	}

	public function testServicesUseLocalTargetOnDispatch()
	{
		$this->expectOutputString('BlueFission\Services\Service');

		$this->object->type = new \Bluefission\Behavioral\Configurable();
		// $this->object->scope = $this->object->type;
		
		$this->object->register('testService', new Handler(new Behavior('DoFirst'), function() { $this->dispatch('DoSecond'); }), Service::LOCAL_LEVEL);;

		$this->object->register('testService', new Handler(new Behavior('DoSecond'), function($data) { echo get_class($data->_target); }), Service::LOCAL_LEVEL);

		$this->object->message('DoFirst');
	}

	public function testServicesUseScopedTargetOnDispatch()
	{
		$this->expectOutputString('Bluefission\Behavioral\Configurable');

		$this->object->type = new \Bluefission\Behavioral\Configurable();
		$this->object->scope = $this->object->type;

		$this->object->register('testService', new Handler(new Behavior('DoFirst'), function() { $this->dispatch('DoSecond'); }), Service::SCOPE_LEVEL);

		$this->object->register('testService', new Handler(new Behavior('DoSecond'), function($data) { echo get_class($data->_target); }), Service::SCOPE_LEVEL);

		$this->object->message('DoFirst');
	}

	public function testCanMakeCallsToInstanceMethods()
	{
		$this->expectOutputString('foobar');

		$this->object->type = '\Bluefission\Behavioral\Configurable';

		$this->object->call('field', array('test', 'foobar'));
		echo $this->object->call('field', array('test'));

	}
}