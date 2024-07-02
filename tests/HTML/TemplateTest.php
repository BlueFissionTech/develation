<?php
namespace BlueFission\Tests\HTML;

use BlueFission\HTML\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase {

    static $testdirectory = '../../testdirectory';

    static $classname = 'BlueFission\HTML\Template';

    static $file = 'sample.txt';

    static $configuration = [
        'file' => 'sample.txt',
        'template_directory' => '../../testdirectory',
        'cache' => true,
        'cache_expire' => 60,
        'cache_directory' => 'cache',
        'max_records' => 1000,
        'delimiter_start' => '{',
        'delimiter_end' => '}',
        'module_token' => 'mod',
        'module_directory' => 'modules',
        'format' => false,
        'eval' => false,
    ];

    protected $object;

    public function setUp() :void {
        chdir(__DIR__);

        touch(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file);

        $data = 'This is a sample text file';

        file_put_contents(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file, $data);

        $this->object = new static::$classname(static::$configuration);
    }

    public function tearDown() :void {
        $testfiles = [
            static::$file,
            'cache'.DIRECTORY_SEPARATOR.static::$file,
            'cache'
        ];

        foreach ($testfiles as $file) {
            if (is_dir(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file)) {
                @rmdir(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file);
            }

            if (file_exists(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file)) {
                @unlink(realpath(static::$testdirectory).DIRECTORY_SEPARATOR.$file);
            }
        }
    }

    public function testConstructor() {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testLoad() {
        $this->assertTrue(is_string($this->object->contents()));
    }

    public function testContents() {
        $expected = 'This is a sample text file';
        $this->object->contents($expected);
        $this->assertEquals($expected, $this->object->contents());

        $actual = $this->object->contents();
        $this->assertEquals($expected, $actual);
    }
    
    public function testReset() {
        $expected = 'This is a sample text file';
        $this->object->contents($expected);
        $this->assertEquals($expected, $this->object->contents());

        $this->object->contents('Changed data');
        $this->assertNotEquals($expected, $this->object->contents());

        $this->object->reset();
        $this->assertEquals($expected, $this->object->contents());
    }

    public function testSet() {
        $this->object->contents('This should alter {test_var}.');
        $var = 'test_var';
        $content = 'This is a test';
        $formatted = true;
        $repetitions = 3;

        $this->object->set($var, $content, $formatted, $repetitions);
        
        $this->assertTrue(strpos($this->object->contents(), $content) !== false);
    }
}
