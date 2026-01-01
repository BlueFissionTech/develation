<?php
namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\IP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class IPTest extends TestCase
{
    private string $testdirectory;
    private string $accessLog;
    private string $ipFile;

    public function setUp(): void
    {
        $this->testdirectory = TestEnvironment::tempDir('bf_ip');
        $this->accessLog = $this->testdirectory . DIRECTORY_SEPARATOR . 'access.log';
        $this->ipFile = $this->testdirectory . DIRECTORY_SEPARATOR . 'ipblock.txt';

        $ref = new \ReflectionClass(IP::class);
        $prop = $ref->getProperty('_storage');
        $prop->setAccessible(true);
        $prop->setValue(null);

        IP::accessLog($this->accessLog);
        IP::ipFile($this->ipFile);

        file_put_contents($this->accessLog, '');
        file_put_contents($this->ipFile, '');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
    }

    public function tearDown(): void
    {
        TestEnvironment::removeDir($this->testdirectory);
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
        $this->assertTrue(IP::deny($ipAddress));
        $this->assertContains(IP::status(), [
            "Blocked IP address $ipAddress",
            "IP address $ipAddress already blocked",
        ]);
        $this->assertStringContainsString($ipAddress, file_get_contents($this->ipFile));
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
