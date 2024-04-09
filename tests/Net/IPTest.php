<?php
namespace BlueFission\Test\Services;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\IP;

class IPTest extends TestCase
{
    static $testdirectory = '/../../../testdirectory';
    static $accessLog = 'access.log';
    static $ipFile = 'ip.txt';

    public function setUp(): void
    {
        IP::accessLog(self::$testdirectory . DIRECTORY_SEPARATOR . self::$accessLog);
        IP::ipFile(self::$testdirectory . DIRECTORY_SEPARATOR . self::$ipFile);
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
        $expected = "Blocking IP address $ipAddress";
        $this->assertEquals($expected, IP::deny($ipAddress));
    }
 
    /**
     * Test allow() method returns the status of IP allowing process
     */
    public function testAllow()
    {
        $ipAddress = '127.0.0.1';
        $expected = "IP Allow Failed";
        $this->assertEquals($expected, IP::allow($ipAddress));
    }
 
    /**
     * Test handle() method returns the status of IP handling process
     */
    public function testHandle()
    {
        $expected = '';
        $this->assertEquals($expected, IP::handle());
    }
 
    /**
     * Test log() method returns the status of the log
     */
    public function testLog()
    {
        $expected = "IP logging failed";
        $this->assertEquals($expected, IP::log());
    }
}
