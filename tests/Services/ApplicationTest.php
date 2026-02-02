<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Application;
use BlueFission\Behavioral\Behaviors\Event;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    public static $classname = 'BlueFission\Services\Application';
    protected $object;

    public function setUp(): void
    {
        // Ensuring singleton instance is reused appropriately
        $this->object = Application::instance();
    }

    public function tearDown(): void
    {
        // Clean up the object after each test
        $this->object = null;
    }

    public function testApplicationComponentsAreAccessible()
    {
        $componentName = 'testComponent';
        $data = ['property' => 'value'];
        $this->object->component($componentName, $data);

        $component = $this->object->field($componentName);

        $this->assertNotNull($component);
        $this->assertEquals('value', $component->field('property'));
    }

    public function testApplicationDelegatesAreAccessible()
    {
        $delegateName = 'testDelegate';
        $this->object->delegate($delegateName, \stdClass::class);

        $service = $this->object->service($delegateName);
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testApplicationCanRouteMessage()
    {
        $this->expectOutputString('Test Output');

        $this->object->register('service1', 'OnEventOne', function ($behavior, $args) {
            echo 'Test ';
        });

        $this->object->register('service2', 'DoEventTwo', function ($behavior, $args) {
            echo 'Output';
        });

        $this->object->route('service1', 'service2', 'OnEventOne', 'DoEventTwo');

        $this->object->perform(new Event('OnEventOne'));
    }

    public function testMessageIsCompleteOnSend()
    {
        $this->object->register('service', 'SendEvent', function ($behavior, $args) {
            echo 'Message Sent';
        });

        $this->expectOutputString('Message Sent');
        $this->object->perform(new Event('SendEvent'));
    }

    public function testServicesMessagesArentGlobal()
    {
        $this->object->register('service1', 'LocalEvent', function ($behavior, $args) {
            echo 'Local ';
        });

        $this->object->register('service2', 'LocalEvent', function ($behavior, $args) {
            echo 'Event';
        });

        $this->expectOutputString('Local ');
        $this->object->service('service1')->perform(new Event('LocalEvent'));
    }

    public function testMessageIsCompleteAfterMultipleRelays()
    {
        $this->object->register('relay1', 'StartEvent', function ($behavior, $args) {
            echo 'Start ';
        });

        $this->object->register('relay2', 'ContinueEvent', function ($behavior, $args) {
            echo 'Continue ';
        });

        $this->object->register('relay3', 'EndEvent', function ($behavior, $args) {
            echo 'End';
        });

        $this->object->route('relay1', 'relay2', 'StartEvent', 'ContinueEvent');
        $this->object->route('relay2', 'relay3', 'ContinueEvent', 'EndEvent');

        $this->expectOutputString('Start Continue End');
        $this->object->perform(new Event('StartEvent'));
    }

    public function testCliOptionsMapToRequest()
    {
        global $argv, $argc;

        $originalArgv = $argv ?? [];
        $originalArgc = $argc ?? 0;
        $originalGet = $_GET ?? [];
        $originalRequest = $_REQUEST ?? [];

        $_GET = [];
        $_REQUEST = [];

        $argv = ['app.php', 'service', 'behavior', 'item', '--foo=bar', '--flag'];
        $argc = count($argv);

        $app = Application::getInstance('CliArgsTest');
        $app->args();

        $request = new \BlueFission\Services\Request();
        $this->assertEquals('bar', $request->all()['foo']);
        $this->assertEquals(true, $request->all()['flag']);

        $argv = $originalArgv;
        $argc = $originalArgc;
        $_GET = $originalGet;
        $_REQUEST = $originalRequest;
    }
}
