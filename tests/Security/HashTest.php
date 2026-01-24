<?php
namespace BlueFission\Tests\Security;

use BlueFission\Security\Hash;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class HashTest extends \PHPUnit\Framework\TestCase
{
    public function testHashAndVerify()
    {
        $hash = new Hash('sha256');
        $digest = $hash->hash('test');

        $this->assertSame(hash('sha256', 'test'), $digest);
        $this->assertTrue($hash->verify('test', $digest));
    }

    public function testHmac()
    {
        $hash = new Hash('sha256');
        $digest = $hash->hmac('payload', 'secret');

        $this->assertSame(hash_hmac('sha256', 'payload', 'secret'), $digest);
    }

    public function testChecksumFile()
    {
        $dir = TestEnvironment::tempDir('bf_hash');
        $path = $dir . DIRECTORY_SEPARATOR . 'sample.txt';
        file_put_contents($path, 'content');

        $hash = new Hash('sha1');
        $digest = $hash->checksumFile($path);

        $this->assertSame(hash_file('sha1', $path), $digest);
        TestEnvironment::removeDir($dir);
    }

    public function testContentId()
    {
        $hash = new Hash('sha256');
        $digest = $hash->contentId('data', null, 'bf');

        $this->assertSame('bf:' . hash('sha256', 'data'), $digest);
    }

    public function testAlgorithmsAndSupport()
    {
        $algorithms = Hash::algorithms();
        $this->assertContains('sha256', $algorithms);
        $this->assertTrue(Hash::supports('sha256'));
        $this->assertFalse(Hash::supports('invalid-algo'));
    }

    public function testUnsupportedAlgorithm()
    {
        $hash = new Hash('sha256');
        $digest = $hash->hash('data', 'invalid-algo');

        $this->assertSame('', $digest);
        $this->assertNotEmpty($hash->errors());
    }
}
