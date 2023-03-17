<?php
namespace BlueFission\Test\Services;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\IP;

class IPTest extends TestCase
{
    /**
     * Test remote() method returns the remote IP address
     */
    public function testRemote()
    {
        $ip = new IP();
        $this->assertEquals($_SERVER['REMOTE_ADDR'], $ip->remote());
    }
 
    /**
     * Test deny() method returns the status of IP blocking process
     */
    public function testDeny()
    {
        $ip = new IP();
        $ipAddress = '127.0.0.1';
        $expected = "Blocking IP address $ipAddress.\n";
        $this->assertEquals($expected, $ip->deny($ipAddress));
    }
 
    /**
     * Test allow() method returns the status of IP allowing process
     */
    public function testAllow()
    {
        $ip = new IP();
        $ipAddress = '127.0.0.1';
        $expected = "IP Allow Failed\n";
        $this->assertEquals($expected, $ip->allow($ipAddress));
    }
 
    /**
     * Test handle() method returns the status of IP handling process
     */
    public function testHandle()
    {
        $ip = new IP();
        $expected = '';
        $this->assertEquals($expected, $ip->handle());
    }
 
    /**
     * Test log() method returns the status of the log
     */
    public function testLog()
    {
        $ip = new IP();
        $file = '/path/to/file';
        $expected = "IP logging failed.\n";
        $this->assertEquals($expected, $ip->log($file));
    }
}
