<?php
namespace BlueFission\Tests;

use BlueFission\Utils\Util;
use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Net\HTTP;
use BlueFission\Net\Email;

class UtilTest extends \PHPUnit\Framework\TestCase {
    public function testEmailAdmin() {
        $enabled = strtolower((string)getenv('DEV_ELATION_EMAIL_TESTS'));
        if (!in_array($enabled, ['1', 'true', 'yes'], true)) {
            $this->markTestSkipped('Email tests are disabled');
        }

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
        $count = 0;
        $max = 2;

        Util::parachute($count, $max);
        $this->assertEquals(1, $count);

        Util::parachute($count, $max);
        $this->assertEquals(2, $count);
    }

    public function testCsrfToken() {
        //Test generating csrf token
        $token = Util::csrfToken();
        $this->assertTrue(Str::is($token));
        $this->assertSame(64, Str::len($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
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

    public function testValueAppliesRequestedFilter() {
        unset($_COOKIE['age'], $_GET['age']);
        $_POST['age'] = '42';

        $value = Util::value('age', FILTER_VALIDATE_INT);
        $this->assertSame(42, $value);

        $_POST['age'] = 'forty-two';

        $value = Util::value('age', FILTER_VALIDATE_INT);
        $this->assertFalse($value);
    }
}
