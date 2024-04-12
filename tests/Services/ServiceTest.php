<?php
namespace BlueFission\Tests\Services;

use BlueFission\Obj;
use BlueFission\Services\Service;
use BlueFission\Services\Application;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Handler;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\Configurable;
 
class ServiceTest extends \PHPUnit\Framework\TestCase {

 	static $classname = 'BlueFission\Services\Service';
 	protected $object;

	public function setUp(): void
	{
		$this->object = new static::$classname();
	}

	    public function testServiceInstance()
    {
        $service = new Service();
        $this->assertInstanceOf(Service::class, $service);
    }

    public function testBroadcastMethod()
    {
        $service = new Service();
        $behavior = $this->createMock(Behavior::class);
        $service->broadcast($behavior);

        $this->assertInstanceOf(Service::class, $behavior->_target);
    }

    public function testBoostMethod()
    {
    	$this->expectOutputString('Test');
        $service1 = new Service();
        $service2 = new Service();
        $service1->name = 'Service1';
        $service2->name = 'Service2';
        // $parent = $this->createMock(Application::class);
        $parent = Application::instance();
        $parent->name("Test");
        $parent->delegate('Service1', $service1);
        $parent->delegate('Service2', $service2);

        $parent->route('Service1', 'Service2', 'Test Behavior', function($behavior) {
        	die('test');
        	echo $this->name;
        });

        $parent->service('Service1')->boost('Test Behavior');
    }

	public function testServicesCanDispatchLocalizedEvents()
	{
		$this->expectOutputString('Test message 1');

		$this->object->type = new class extends Obj {
			use Configurable;
		};

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

		$this->object->type = new class extends Obj {
			use Configurable;
		};

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

		$this->object->type = new class extends Obj {
			use Configurable;
		};

		$behavior = new Behavior('Test behavior');

		$this->object->scope = $this;

		$test_var = "foo";

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

		$this->object->type = new class extends Obj {
			use Configurable;
		};

		$behavior = new Behavior('Test behavior');

		$test_var = "foo";

		$this->object->test_var = "bar";

		$handler = new Handler($behavior, function( $data ) {
			echo $test_var;
		});

		$this->object->register('testService', $handler);

		$this->object->message('Test behavior');
	}

	public function testServicesUseLocalTargetOnDispatch()
	{
		$this->expectOutputString('BlueFission\Services\Service');

		$this->object->type = new class extends Obj {
			use Configurable;
		};
		// $this->object->scope = $this->object->type;
		
		$this->object->register('testService', new Handler(new Behavior('DoFirst'), function() { $this->dispatch('DoSecond'); }), Service::LOCAL_LEVEL);;

		$this->object->register('testService', new Handler(new Behavior('DoSecond'), function($data) { echo get_class($data->_target); }), Service::LOCAL_LEVEL);

		$this->object->message('DoFirst');
	}

	public function testServicesUseScopedTargetOnDispatch()
	{
		$this->expectOutputString('Bluefission\Behavioral\Configurable');

		$this->object->type = new class extends Obj {
			use Configurable;
		};
		$this->object->scope = $this->object->type;

		$this->object->register('testService', new Handler(new Behavior('DoFirst'), function() { $this->dispatch('DoSecond'); }), Service::SCOPE_LEVEL);

		$this->object->register('testService', new Handler(new Behavior('DoSecond'), function($data) { echo get_class($data->_target); }), Service::SCOPE_LEVEL);

		$this->object->message('DoFirst');
	}

	public function testCanMakeCallsToInstanceMethods()
	{
		$this->expectOutputString('foobar');

		$this->object->type = new class extends Obj {
			use Configurable;
		};

		$this->object->call('field', array('test', 'foobar'));
		echo $this->object->call('field', array('test'));

	}
}