<?php

namespace BlueFission\Tests\Services;

use BlueFission\Data\Storage\Storage;
use BlueFission\Services\Authenticator;
use PHPUnit\Framework\TestCase;

class AuthenticatorSessionWriteTest extends TestCase
{
    private bool $hadCookie;
    private mixed $cookie;

    protected function setUp(): void
    {
        $this->hadCookie = array_key_exists('login', $_COOKIE);
        $this->cookie = $_COOKIE['login'] ?? null;
        unset($_COOKIE['login']);
    }

    protected function tearDown(): void
    {
        if ($this->hadCookie) {
            $_COOKIE['login'] = $this->cookie;
        } else {
            unset($_COOKIE['login']);
        }
    }

    public function testOnlySuccessfulWriteStatusEstablishesSession(): void
    {
        foreach ([Storage::STATUS_FAILED, false, null] as $status) {
            $authenticator = $this->authenticator($status);
            self::assertFalse($authenticator->setSession());
        }

        self::assertTrue($this->authenticator(Storage::STATUS_SUCCESS)->setSession());
    }

    public function testThrowingWriteDoesNotEstablishSession(): void
    {
        $authenticator = $this->authenticator(Storage::STATUS_SUCCESS, true);

        self::assertFalse($authenticator->setSession());
    }

    public function testFailedAttemptDoesNotMakeLaterSuccessAmbiguous(): void
    {
        $storedStatus = Storage::STATUS_SUCCESS;
        $writes = 0;
        $session = $this->session(function ($message = null) use (&$storedStatus) {
            if ($message !== null) {
                $storedStatus = $message;
                return null;
            }

            return $storedStatus;
        });
        $session->method('write')->willReturnCallback(function () use ($session, &$storedStatus, &$writes) {
            if (++$writes === 2) {
                $storedStatus = Storage::STATUS_SUCCESS;
            }

            return $session;
        });
        $authenticator = new Authenticator($session, $this->createMock(Storage::class));

        self::assertFalse($authenticator->setSession());
        self::assertTrue($authenticator->setSession());
    }

    private function authenticator(mixed $status, bool $throw = false): Authenticator
    {
        $session = $this->session($status);
        if ($throw) {
            $session->method('write')->willThrowException(new \RuntimeException('storage failed'));
        } else {
            $session->method('write')->willReturnSelf();
        }

        return new Authenticator($session, $this->createMock(Storage::class));
    }

    private function session(mixed $status): Storage
    {
        $session = $this->createMock(Storage::class);
        $session->method('config')->willReturnSelf();
        $session->method('activate')->willReturnSelf();
        $session->method('read')->willReturnSelf();
        $session->method('data')->willReturn(['username' => 'fixture', 'id' => 42]);
        $session->method('clear')->willReturnSelf();
        if ($status instanceof \Closure) {
            $session->method('status')->willReturnCallback($status);
        } else {
            $session->method('status')->willReturn($status);
        }

        return $session;
    }
}
