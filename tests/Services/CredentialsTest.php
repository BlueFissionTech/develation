<?php

namespace BlueFission\Tests\Services;

use PHPUnit\Framework\TestCase;
use BlueFission\Services\Credentials;

class CredentialsTest extends TestCase
{
    public function testValidateMethod()
    {
        $credentials = new Credentials();
        $this->assertFalse($credentials->validate());

        $credentials->username = "testuser";
        $this->assertFalse($credentials->validate());

        $credentials->password = "testpass";
        $this->assertTrue($credentials->validate());
    }
}
