<?php

namespace BlueFission\Tests;

use BlueFission\Utils\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    protected function tearDown(): void
    {
        $loader = Loader::instance();
        $loader->config('default_extension', 'php');
        $loader->config('full_stop', '.');
    }

    public function testInstanceIsSingleton()
    {
        $loader1 = Loader::instance();
        $loader2 = Loader::instance();
        $this->assertSame($loader1, $loader2);
    }

    public function testConfigReturnsCorrectValue()
    {
        $loader = Loader::instance();
        $this->assertEquals('php', $loader->config('default_extension'));
    }

    public function testConfigSetsValue()
    {
        $loader = Loader::instance();
        $loader->config('default_extension', 'js');
        $this->assertEquals('js', $loader->config('default_extension'));
    }

    public function testConfigAcceptsArrayUpdatesForKnownKeys()
    {
        $loader = Loader::instance();
        $loader->config([
            'default_extension' => 'inc',
            'full_stop' => ':',
            'unknown' => 'ignored',
        ]);

        $this->assertEquals('inc', $loader->config('default_extension'));
        $this->assertEquals(':', $loader->config('full_stop'));
        $this->assertNull($loader->config('unknown'));
    }

    public function testLoadClass()
    {
        $loader = Loader::instance();
        $this->assertFalse($loader->load('NotExistingClass'));
    }

    public function testLoadResolvesConfiguredPath()
    {
        $loader = Loader::instance();
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('loader-fixture-', true);
        $class = 'LoaderFixture' . str_replace('.', '', uniqid('', true));

        mkdir($dir);
        $file = $dir . DIRECTORY_SEPARATOR . $class . '.php';
        file_put_contents($file, "<?php class {$class} {}\n");

        try {
            $loader->addPath($dir);
            $this->assertNull($loader->load($class));
            $this->assertTrue(class_exists($class, false));
        } finally {
            if (file_exists($file)) {
                unlink($file);
            }

            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    public function testRepeatableLoadIsOptInAndDefaultRemainsOnce(): void
    {
        $loader = Loader::instance();
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('loader-repeat-', true);
        $name = 'RepeatFixture' . str_replace('.', '', uniqid('', true));
        $counter = 'loader_repeat_' . $name;
        mkdir($dir);
        $file = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        file_put_contents($file, "<?php \$GLOBALS['{$counter}'] = (\$GLOBALS['{$counter}'] ?? 0) + 1;\n");

        try {
            $loader->addPath($dir);
            $this->assertNull($loader->load($name));
            $this->assertNull($loader->load($name));
            $this->assertSame(1, $GLOBALS[$counter]);
            $this->assertNull($loader->load($name, false));
            $this->assertNull($loader->load($name, false));
            $this->assertSame(3, $GLOBALS[$counter]);
        } finally {
            unset($GLOBALS[$counter]);
            unlink($file);
            rmdir($dir);
        }
    }

    public function testRepeatableLoadCanRetryAfterAThrownFixture(): void
    {
        $loader = Loader::instance();
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('loader-retry-', true);
        $name = 'RetryFixture' . str_replace('.', '', uniqid('', true));
        $counter = 'loader_retry_' . $name;
        mkdir($dir);
        $file = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        file_put_contents($file, "<?php if (!isset(\$GLOBALS['{$counter}'])) { \$GLOBALS['{$counter}'] = 1; throw new \\RuntimeException('first load'); } \$GLOBALS['{$counter}']++;\n");

        try {
            $loader->addPath($dir);
            try {
                $loader->load($name, false);
                $this->fail('Expected the first load to throw.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('first load', $exception->getMessage());
            }
            $this->assertNull($loader->load($name, false));
            $this->assertSame(2, $GLOBALS[$counter]);
        } finally {
            unset($GLOBALS[$counter]);
            unlink($file);
            rmdir($dir);
        }
    }
}
