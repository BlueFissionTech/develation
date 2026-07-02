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

    public function testNamedApplicationInstancesAreReused()
    {
        $first = Application::getInstance('InstanceHelperTest');
        $second = Application::getInstance('InstanceHelperTest');

        $this->assertSame($first, $second);
    }

    public function testApplicationInstanceReturnsFirstRegisteredInstance()
    {
        $first = Application::instance();
        Application::getInstance('LaterInstanceHelperTest');

        $this->assertSame($first, Application::instance());
    }

    public function testApplicationReadsFilesFromWebRootFirst()
    {
        $root = $this->makeTempDirectory('web-root');
        $assets = $this->makeTempDirectory('asset-root');

        try {
            file_put_contents($root . DIRECTORY_SEPARATOR . 'app.css', 'web-root');
            file_put_contents($assets . DIRECTORY_SEPARATOR . 'app.css', 'asset-root');

            $app = Application::getInstance('AssetWebRootTest');
            $app->webRoot($root);
            $app->assetDir($assets);

            $this->assertTrue($app->fileExists('app.css'));
            $this->assertSame('web-root', $app->fileContents('app.css'));
        } finally {
            $this->removeTempDirectory($root);
            $this->removeTempDirectory($assets);
        }
    }

    public function testApplicationFallsBackToAssetDirectoryForFiles()
    {
        $root = $this->makeTempDirectory('empty-root');
        $assets = $this->makeTempDirectory('asset-root');

        try {
            file_put_contents($assets . DIRECTORY_SEPARATOR . 'app.js', 'asset-root');

            $app = Application::getInstance('AssetFallbackTest');
            $app->webRoot($root);
            $app->assetDir($assets);

            $this->assertTrue($app->fileExists('app.js'));
            $this->assertSame('asset-root', $app->fileContents('app.js'));
            $this->assertNull($app->fileContents('missing.js'));
        } finally {
            $this->removeTempDirectory($root);
            $this->removeTempDirectory($assets);
        }
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

    public function testCliNoOptionsMapToRequest()
    {
        global $argv, $argc;

        $originalArgv = $argv ?? [];
        $originalArgc = $argc ?? 0;
        $originalGet = $_GET ?? [];
        $originalRequest = $_REQUEST ?? [];

        $_GET = [];
        $_REQUEST = [];

        $argv = ['app.php', 'service', 'behavior', '--no-cache'];
        $argc = count($argv);

        $app = Application::getInstance('CliNoArgsTest');
        $app->args();

        $request = new \BlueFission\Services\Request();
        $this->assertFalse($request->all()['cache']);

        $argv = $originalArgv;
        $argc = $originalArgc;
        $_GET = $originalGet;
        $_REQUEST = $originalRequest;
    }

    public function testArgsUseRequestMethodAndUriDefaults()
    {
        global $argv, $argc;

        $originalArgv = $argv ?? [];
        $originalArgc = $argc ?? 0;
        $originalGet = $_GET ?? [];
        $originalPost = $_POST ?? [];
        $originalServer = $_SERVER;

        $argv = ['app.php'];
        $argc = count($argv);
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['REQUEST_URI'] = '/catalog/list/active';

        $app = Application::getInstance('RequestDefaultArgsTest');
        $app->args();

        $arguments = $this->applicationArguments($app);

        $this->assertSame('post', $arguments['_method']);
        $this->assertSame('catalog', $arguments['service']);
        $this->assertSame('list', $arguments['behavior']);
        $this->assertSame(['active'], $arguments['data']);

        $argv = $originalArgv;
        $argc = $originalArgc;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_SERVER = $originalServer;
    }

    private function applicationArguments(Application $app): array
    {
        $reflection = new \ReflectionClass($app);
        $property = $reflection->getProperty('_arguments');
        $property->setAccessible(true);

        return $property->getValue($app);
    }

    private function makeTempDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid($prefix . '-', true);
        mkdir($directory);

        return $directory;
    }

    private function removeTempDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($directory);
    }
}
