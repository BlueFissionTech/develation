<?php
namespace BlueFission\Tests\HTML;

use BlueFission\HTML\HTML;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase {
    public function testConstructor() {
        $config = array(
            'file' => 'sample.txt',
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
        );

        $template = new Template($config);
        $this->assertInstanceOf(Template::class, $template);
    }

    public function testLoad() {
        $template = new Template('sample.txt');
        $template->load('sample.txt');
        $this->assertTrue(is_string($template->contents()));
    }

    public function testContents() {
        $template = new Template('sample.txt');
        $template->load('sample.txt');

        $expected = 'This is a sample text file';
        $template->contents($expected);
        $this->assertEquals($expected, $template->contents());

        $actual = $template->contents();
        $this->assertEquals($expected, $actual);
    }

    public function testClear() {
        $template = new Template('sample.txt');
        $template->load('sample.txt');
        $template->clear();
        $this->assertEquals('', $template->contents());
    }

    public function testReset() {
        $template = new Template('sample.txt');
        $template->load('sample.txt');

        $expected = 'This is a sample text file';
        $template->contents($expected);
        $this->assertEquals($expected, $template->contents());

        $template->reset();
        $this->assertNotEquals($expected, $template->contents());
    }

    public function testSet() {
        $template = new Template('sample.txt');
        $template->load('sample.txt');

        $var = 'test_var';
        $content = 'This is a test';
        $formatted = true;
        $repetitions = 3;

        $template->set($var, $content, $formatted, $repetitions);
        $this->assertTrue(strpos($template->contents(), $content) !== false);
    }
}
