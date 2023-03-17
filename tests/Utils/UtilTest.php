<?php
namespace BlueFission\Tests;

use BlueFission\Utils\Util;
use BlueFission\DevValue;
use BlueFission\Net\HTTP;
use BlueFission\Net\Email;

class UtilTest extends \PHPUnit_Framework_TestCase {
    public function testEmailAdmin() {
        //Test sending email with all default values
        $status = Util::emailAdmin();
        $this->assertTrue($status);

        //Test sending email with custom values
        $message = "Test Message";
        $subject = "Test Subject";
        $from = "test@test.com";
        $rcpt = "test@test.com";

        $status = Util::emailAdmin($message, $subject, $from, $rcpt);
        $this->assertTrue($status);
    }

    public function testParachute() {
        //Test exceeding max count and exiting with log and alert
        $count = 500;
        $max = 400;
        $redirect = "";
        $log = true;
        $alert = true;

        $this->expectExceptionCode(0);
        Util::parachute($count, $max, $redirect, $log, $alert);
    }

    public function testCsrfToken() {
        //Test generating csrf token
        $token = Util::csrf_token();
        $this->assertTrue(is_string($token));
    }

    public function testValue() {
        //Test getting value from cookie, post or get with all defaults
        $_COOKIE['test'] = 'cookie_value';
        $_GET['test'] = 'get_value';
        $_POST['test'] = 'post_value';

        $value = Util::value('test');
        $this->assertEquals('cookie_value', $value);

        //Test getting value from post or get with cookie missing
        unset($_COOKIE['test']);

        $value = Util::value('test');
        $this->assertEquals('post_value', $value);

        //Test getting value from get with post and cookie missing
        unset($_POST['test']);

        $value = Util::value('test');
        $this->assertEquals('get_value', $value);
    }
}
