<?php
namespace BlueFission\Test\Services;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\IP;

class IPTest extends TestCase
{
    static $testdirectory = 'testdirectory';
    static $accessLog = 'access.log';
    static $ipFile = 'ipblock.txt';

    public function setUp(): void
    {
        IP::accessLog(self::$testdirectory . DIRECTORY_SEPARATOR . self::$accessLog);
        IP::ipFile(self::$testdirectory . DIRECTORY_SEPARATOR . self::$ipFile);
    }

    public function tearDown(): void
    {
        if (file_exists(self::$testdirectory . DIRECTORY_SEPARATOR . self::$accessLog)) {
            unlink(self::$testdirectory . DIRECTORY_SEPARATOR . self::$accessLog);
        }

        if (file_exists(self::$testdirectory . DIRECTORY_SEPARATOR . self::$ipFile)) {
            unlink(self::$testdirectory . DIRECTORY_SEPARATOR . self::$ipFile);
        }
    }

    /**
     * Test remote() method returns the remote IP address
     */
    public function testRemote()
    {
        $this->assertEquals($_SERVER['REMOTE_ADDR'], IP::remote());
    }
 
    /**
     * Test deny() method returns the status of IP blocking process
     */
    public function testDeny()
    {
        $ipAddress = '127.0.0.1';
        $expected = "Blocked IP address $ipAddress";
        $this->assertTrue(IP::deny($ipAddress));
        $this->assertEquals($expected, IP::status());
    }
 
    /**
     * Test allow() method returns the status of IP allowing process
     */
    public function testAllow()
    {
        $ipAddress = '127.0.0.1';
        $expected = "IP address 127.0.0.1 already allowed";
        $this->assertTrue(IP::allow($ipAddress));
        $this->assertEquals($expected, IP::status());
    }
 
    /**
     * Test handle() method returns the status of IP handling process
     */
    public function testHandle()
    {
        $expected = "Your IP address has been restricted from viewing this content. Please contact the administrator.";
        $ipAddress = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = $ipAddress;

        $this->assertTrue(IP::handle());
        $this->assertEquals("IP Allowed", IP::status());

        IP::deny($ipAddress);

        $this->assertFalse(IP::handle());
        $this->assertEquals($expected, IP::status());

        IP::allow($ipAddress);
    }
 
    /**
     * Test log() method returns the status of the log
     */
    public function testLog()
    {
        $expected = "IP logging successful";
        $this->assertTrue(IP::log());
        $this->assertEquals($expected, IP::status());
    }
}
