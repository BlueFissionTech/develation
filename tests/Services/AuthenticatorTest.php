<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Authenticator;
use BlueFission\Data\Storage\Storage;
use BlueFission\Data\Storage\Cookie;
use PHPUnit\Framework\TestCase;

class AuthenticatorTest extends TestCase
{
    private $authenticator;

    public function setUp(): void
    {
        $session = new Cookie();
        $datasource = $this->createMock(Storage::class);
        $config = null;
        $this->authenticator = new Authenticator($session, $datasource, $config);
    }

    public function testAuthenticateReturnsFalseForEmptyUsernameOrPassword()
    {
        $username = "";
        $password = "password";

        $this->assertFalse($this->authenticator->authenticate($username, $password));

        $username = "username";
        $password = "";

        $this->assertFalse($this->authenticator->authenticate($username, $password));
    }

    public function testIsAuthenticatedReturnsTrueForValidCookie()
    {
        $_COOKIE[$this->authenticator->config('session')] = json_encode([
            'username' => 'username',
            'id' => 1
        ]);

        $this->assertTrue($this->authenticator->isAuthenticated());
    }

    public function testIsAuthenticatedReturnsFalseForInvalidCookie()
    {
        $_COOKIE[$this->authenticator->config('session')] = json_encode([
            'username' => '',
            'id' => ''
        ]);

        $this->assertFalse($this->authenticator->isAuthenticated());
    }

    public function testClearIPAddressDeletesLoginAttemptThroughDatasource()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $session = new Cookie();
        $datasource = $this->createMock(Storage::class);
        $datasource->method('config')->willReturnSelf();
        $datasource->method('activate')->willReturnSelf();
        $datasource->method('clear')->willReturnSelf();
        $datasource->method('field')->willReturnSelf();
        $datasource->method('read')->willReturnSelf();
        $datasource->method('data')->willReturn([
            'last_attempt' => date('Y-m-d G:i:s', strtotime('+1 minute')),
        ]);
        $datasource->expects($this->once())->method('delete')->willReturnSelf();

        $authenticator = new Authenticator($session, $datasource);
        $method = new \ReflectionMethod($authenticator, 'clearIPAddress');
        $method->setAccessible(true);
        $method->invoke($authenticator);
    }
}
