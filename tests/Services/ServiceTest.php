<?php

namespace BlueFission\Tests\Services;

use BlueFission\Obj;
use BlueFission\Services\Service;
use BlueFission\Services\Application;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Handler;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\Configurable;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public static $classname = 'BlueFission\Services\Service';
    protected $object;
    public $test_var;

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

        $this->assertInstanceOf(Service::class, $behavior->target);
    }

    public function testBoostMethod()
    {
        $this->expectOutputString('Test');
        $service1 = new Service();
        $service2 = new Service();
        $service1->name = 'Service1A';
        $service2->name = 'Service2A';
        // $parent = $this->createMock(Application::class);
        $parent = Application::instance();
        $parent->name("Test");
        $parent->delegate('Service1B', $service1);
        $parent->delegate('Service2B', $service2);

        $parent->route('Service1B', 'Service2B', 'Test Behavior', function ($behavior) {
            echo 'Test';
        });

        // die($parent->service('Service1B')->name());

        $parent->service('Service1B')->boost('Test Behavior');
    }

    public function testServicesCanDispatchLocalizedEvents()
    {
        $this->expectOutputString('Test message 1');

        $this->object->type = new class () extends Obj {
            use Configurable;

            public function __construct()
            {
                parent::__construct();
                $this->bootstrapConfig();
            }
        };

        $behavior = new Behavior('Test behavior');

        $handler = new Handler($behavior, function () {
            echo "Test message 1";
        });

        $this->object->register('testService', $handler, Service::LOCAL_LEVEL);

        $this->object->message('Test behavior');
    }

    public function testServicesCanDispatchScopedEvents()
    {
        $this->expectOutputString('Test message 2');

        $this->object->type = new class () extends Obj {
            use Configurable;

            public function __construct()
            {
                parent::__construct();
                $this->bootstrapConfig();
            }
        };

        $behavior = new Behavior('Test behavior');

        $handler = new Handler($behavior, function () {
            echo "Test message 2";
        });

        $this->object->register('testService', $handler, Service::SCOPE_LEVEL);

        $this->object->message('Test behavior');
    }

    public function testServicesUseLocalPropertiesOnDispatch()
    {
        $this->expectOutputString('foo');

        $this->object->type = new class () extends Obj {
            use Configurable;

            public function __construct()
            {
                parent::__construct();
                $this->bootstrapConfig();
            }
        };

        $behavior = new Behavior('Test behavior');

        $this->object->scope = $this;

        $this->test_var = "foo";

        $this->object->test_var = "bar";

        $handler = new Handler($behavior, function ($data) {
            echo $this->test_var;
        });

        $this->object->register('testService', $handler);

        $this->object->message('Test behavior');
    }

    public function testServicesUseScopedPropertiesOnDispatch()
    {
        $this->expectOutputString('bar');

        $this->object->type = new class () extends Obj {
            use Configurable;

            public function __construct()
            {
                parent::__construct();
                $this->bootstrapConfig();
            }
        };

        $behavior = new Behavior('Test behavior');

        $this->test_var = "foo";

        $this->object->test_var = "bar";

        $handler = new Handler($behavior, function ($data) {
            echo $this->test_var;
        });

        $this->object->register('testService', $handler);

        $this->object->message('Test behavior');
    }

    public function testServicesUseLocalTargetOnDispatch()
    {
        $this->expectOutputString('BlueFission\Services\Service');

        $this->object->type = 'BlueFission\Services\Service';
        // $this->object->type = new class extends Obj {
        // 	use Configurable;
        // };
        $this->object->scope = $this->object;

        $this->object->register('testService', new Handler(new Behavior('DoFirst'), function () { $this->message('DoSecond'); }), Service::LOCAL_LEVEL);
        ;

        $this->object->register('testService', new Handler(new Behavior('DoSecond'), function ($behavior) { echo get_class($behavior->target); }), Service::LOCAL_LEVEL);

        $this->object->message('DoFirst');
    }

    public function testServicesUseScopedTargetOnDispatch()
    {
        $this->expectOutputString('BlueFission\Obj');

        $this->object->type = Obj::class;

        $this->object->scope = $this->object->type;

        $this->object->register('testService', new Handler(new Behavior('DoFirst'), function () { $this->dispatch('DoSecond'); }), Service::SCOPE_LEVEL);

        $this->object->register('testService', new Handler(new Behavior('DoSecond'), function ($behavior) { echo get_class($behavior->target); }), Service::SCOPE_LEVEL);

        $this->object->message('DoFirst');
    }

    public function testCanMakeCallsToInstanceMethods()
    {
        $this->expectOutputString('foobar');

        $this->object->type = new class () extends Obj {
            use Configurable;

            public function __construct()
            {
                parent::__construct();
                $this->bootstrapConfig();
            }
        };

        $this->object->instance();

        $this->object->call('field', ['test', 'foobar']);
        echo $this->object->call('field', ['test']);
    }
}
