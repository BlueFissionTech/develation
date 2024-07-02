<?php
namespace BlueFission\Tests\Services;

use BlueFission\Services\Authenticator;
use BlueFission\Data\Storage\Storage;
use BlueFission\Data\Storage\Cookie;
use PHPUnit\Framework\TestCase;

class AuthenticatorTest extends TestCase {
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
}
