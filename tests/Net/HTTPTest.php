<?php
namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\HTTP;

class HTTPTest extends TestCase {

    public function testQuery() {
        $formdata = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        $numeric_prefix = 'prefix_';
        $key = 'test_key';
        $expected = 'test_key%5Bkey1%5D=value1&test_key%5Bkey2%5D=value2';
        $actual = HTTP::query($formdata, $numeric_prefix, $key);
        $this->assertEquals($expected, $actual);
    }

    public function testUrlExists() {
        $this->assertTrue(HTTP::urlExists('http://www.google.com'));
        $this->assertFalse(HTTP::urlExists('http://nonexistenturl.com'));
    }

    public function testDomain() {
        $_SERVER['HTTP_HOST'] = 'www.google.com';
        $expected = 'google.com';
        $actual = HTTP::domain();
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTP_HOST'] = 'www.google.com';
        $expected = 'www.google.com';
        $actual = HTTP::domain(true);
        $this->assertEquals($expected, $actual);
    }

    public function testUrl() {
        $_SERVER['HTTP_HOST'] = 'www.google.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTPS'] = 'on';
        $expected = 'https://www.google.com/test';
        $actual = HTTP::url();
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTP_HOST'] = 'www.google.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTPS'] = '';
        $expected = 'http://www.google.com/test';
        $actual = HTTP::url();
        $this->assertEquals($expected, $actual);
    }

    public function testRef() {
        $_SERVER['HTTP_REFERER'] = 'https://www.google.com';
        $expected = 'https://www.google.com';
        $actual = HTTP::ref();
        $this->assertEquals($expected, $actual);
    }

}
