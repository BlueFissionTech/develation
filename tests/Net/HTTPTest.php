<?php
namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class HTTPTest extends TestCase {

    public function testQuery() {
        $formdata = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        $numeric_prefix = 'prefix_';
        $key = 'test_key';
        $expected = 'test_key[key1]=value1&test_key[key2]=value2';
        $actual = HTTP::query($formdata, $numeric_prefix, $key);
        $this->assertEquals($expected, $actual);
    }

    public function testUrlExists() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->assertFalse(HTTP::urlExists('ftp://example.com'));
            return;
        }

        $url = getenv('DEV_ELATION_HTTP_TEST_URL') ?: 'https://www.bluefission.com';
        $this->assertTrue(HTTP::urlExists($url));
        $this->assertFalse(HTTP::urlExists('http://nonexistent.invalid'));
    }

    public function testDomain() {
        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $expected = '.bluefission.com';
        $actual = HTTP::domain();
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $expected = 'www.bluefission.com';
        $actual = HTTP::domain(true);
        $this->assertEquals($expected, $actual);
    }

    public function testUrl() {
        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTPS'] = 'on';
        $expected = 'https://www.bluefission.com/test';
        $actual = HTTP::url();
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTPS'] = '';
        $expected = 'http://www.bluefission.com/test';
        $actual = HTTP::url();
        $this->assertEquals($expected, $actual);
    }

}
