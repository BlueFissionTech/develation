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

    public function testLoginAttemptsCanUseInjectedStorage()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $session = new Cookie();
        $datasource = $this->createMock(Storage::class);
        $datasource->expects($this->never())->method('config');

        $attempts = $this->createMock(Storage::class);
        $attempts->method('config')->willReturnSelf();
        $attempts->method('activate')->willReturnSelf();
        $attempts->method('field')->willReturnSelf();
        $attempts->method('read')->willReturnSelf();
        $attempts->method('data')->willReturn([]);
        $attempts->expects($this->once())->method('write')->willReturnSelf();

        $authenticator = new Authenticator($session, $datasource, null, $attempts);
        $method = new \ReflectionMethod($authenticator, 'confirmIPAddress');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($authenticator, '127.0.0.1'));
    }

    public function testFreshLoginAttemptDoesNotEmitTimestampWarnings()
    {
        $fields = [];
        $authenticator = $this->authenticatorWithAttemptData([
            'last_attempt' => null,
            'attempts' => 0,
        ], $fields);
        $hadRemoteAddress = array_key_exists('REMOTE_ADDR', $_SERVER);
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $this->assertTrue($this->invokeAuthenticatorMethod(
                $authenticator,
                'confirmIPAddress',
                ['127.0.0.1']
            ));
            $this->assertFalse($this->invokeAuthenticatorMethod(
                $authenticator,
                'blockIPAddress'
            ));
            $this->assertNull($this->invokeAuthenticatorMethod(
                $authenticator,
                'clearIPAddress'
            ));
        } finally {
            restore_error_handler();

            if ($hadRemoteAddress) {
                $_SERVER['REMOTE_ADDR'] = $remoteAddress;
            } else {
                unset($_SERVER['REMOTE_ADDR']);
            }
        }

        $this->assertSame(600, $authenticator->config('lockout_interval'));
        $this->assertSame(0, $fields['attempts']);
        $this->assertNotEmpty($fields['last_attempt']);
    }

    public function testLoginAttemptWithinWindowIncrementsCount()
    {
        $fields = [];
        $authenticator = $this->authenticatorWithAttemptData([
            'last_attempt' => date('Y-m-d H:i:s', time() - 60),
            'attempts' => 4,
        ], $fields);

        $this->assertTrue($this->invokeAuthenticatorMethod(
            $authenticator,
            'confirmIPAddress',
            ['127.0.0.1']
        ));
        $this->assertSame(5, $fields['attempts']);
    }

    public function testExpiredLoginAttemptResetsCount()
    {
        $fields = [];
        $authenticator = $this->authenticatorWithAttemptData([
            'last_attempt' => date('Y-m-d H:i:s', time() - 601),
            'attempts' => 9,
        ], $fields);

        $this->assertTrue($this->invokeAuthenticatorMethod(
            $authenticator,
            'confirmIPAddress',
            ['127.0.0.1']
        ));
        $this->assertSame(0, $fields['attempts']);
    }

    public function testMaximumLoginAttemptsAreRejected()
    {
        $fields = [];
        $authenticator = $this->authenticatorWithAttemptData([
            'last_attempt' => date('Y-m-d H:i:s', time() - 60),
            'attempts' => 9,
        ], $fields);

        $this->assertFalse($this->invokeAuthenticatorMethod(
            $authenticator,
            'confirmIPAddress',
            ['127.0.0.1']
        ));
        $this->assertSame(10, $fields['attempts']);
    }

    public function testDestroySessionClearsAuthenticatedStorageWithoutWarnings()
    {
        $authenticator = $this->authenticatorWithExpectedSessionDeletion();
        $authenticator->assign([
            'username' => 'username',
            'displayname' => 'Display Name',
            'id' => 42,
        ]);

        $this->assertWarningFreeSessionDestruction($authenticator);
        $this->assertSame('', $authenticator->username);
        $this->assertSame('', $authenticator->displayname);
        $this->assertSame(0, $authenticator->id);
    }

    public function testDestroySessionIsWarningFreeWhenSessionIsAlreadyEmpty()
    {
        $authenticator = $this->authenticatorWithExpectedSessionDeletion();

        $this->assertWarningFreeSessionDestruction($authenticator);
    }

    private function authenticatorWithAttemptData(array $data, array &$fields): Authenticator
    {
        $session = new Cookie();
        $datasource = $this->createMock(Storage::class);
        $attempts = $this->createMock(Storage::class);

        $attempts->method('config')->willReturnSelf();
        $attempts->method('activate')->willReturnSelf();
        $attempts->method('read')->willReturnSelf();
        $attempts->method('data')->willReturn($data);
        $attempts->method('write')->willReturnSelf();
        $attempts->method('field')->willReturnCallback(
            function ($field, $value = null) use (&$fields, $attempts) {
                $fields[$field] = $value;

                return $attempts;
            }
        );

        return new Authenticator($session, $datasource, null, $attempts);
    }

    private function invokeAuthenticatorMethod(
        Authenticator $authenticator,
        string $method,
        array $arguments = []
    ): mixed {
        $reflection = new \ReflectionMethod($authenticator, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($authenticator, $arguments);
    }

    private function authenticatorWithExpectedSessionDeletion(): Authenticator
    {
        $session = $this->createMock(Storage::class);
        $session->method('config')->willReturnSelf();
        $session->method('activate')->willReturnSelf();
        $session->expects($this->once())->method('clear')->willReturnSelf();
        $session->expects($this->once())->method('write')->willReturnSelf();
        $session->expects($this->once())->method('delete')->willReturnSelf();
        $session->expects($this->never())->method('field');

        $datasource = $this->createMock(Storage::class);

        return new Authenticator($session, $datasource);
    }

    private function assertWarningFreeSessionDestruction(Authenticator $authenticator): void
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $this->assertTrue($authenticator->destroySession());
        } finally {
            restore_error_handler();
        }
    }
}
