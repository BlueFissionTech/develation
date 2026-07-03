<?php

namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
use BlueFission\Arr;
use BlueFission\Data\FileSystem;
use BlueFission\Net\Email;

class EmailTest extends TestCase
{
    public function testConstructor()
    {
        $email = new Email('test@example.com', 'test@example.com', 'Test Subject', 'Test Message', 'cc@example.com', 'bcc@example.com', true, ['Test Headers'], 'Test Additional');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame(['test@example.com'], $email->getRecipients(Email::TO));
        $this->assertSame(['cc@example.com'], $email->getRecipients(Email::CC));
        $this->assertSame(['bcc@example.com'], $email->getRecipients(Email::BCC));
        $this->assertSame('test@example.com', $email->from());
        $this->assertSame('Test Subject', $email->subject());
        $this->assertSame('Test Message', $email->body());
        $this->assertSame(['Test Headers'], $email->headers());
    }

    public function testConstructorAppliesAttachments()
    {
        $attachment = ['file' => __FILE__, 'type' => 'text/plain'];
        $email = new Email('test@example.com', 'test@example.com', 'Test Subject', 'Test Message', null, null, false, null, null, [$attachment]);

        $this->assertSame([$attachment], $email->attach());
    }

    public function testEmailCollectionsUseArrMembers()
    {
        $email = new Email();

        $this->assertInstanceOf(Arr::class, $this->emailProperty($email, '_config'));
        $this->assertInstanceOf(Arr::class, $this->emailProperty($email, '_data'));
        $this->assertInstanceOf(Arr::class, $this->emailProperty($email, '_headers'));
        $this->assertInstanceOf(Arr::class, $this->emailProperty($email, '_attachments'));
        $this->assertInstanceOf(Arr::class, $this->emailProperty($email, '_recipients'));
        $this->assertSame([], $email->headers());
        $this->assertSame([], $email->attach());
        $this->assertSame([], $email->recipients());
    }

    public function testField()
    {
        $email = new Email();

        $this->assertNull($email->field('invalid_field'));
        $this->assertSame('Test Subject', $email->subject('Test Subject')->subject());
        $this->assertSame('Test Subject', $email->field('subject'));
    }

    public function testHeaders()
    {
        $email = new Email();

        $this->assertSame([], $email->headers());
        $this->assertFalse($email->headers('invalid_header'));
        $this->assertSame('Test Header', $email->headers('test_header', 'Test Header')->headers('test_header'));
        $this->assertSame('Test Header', $email->headers('test_header'));
    }

    public function testAttachmentPayloadUsesReadableFile()
    {
        $file = tempnam(sys_get_temp_dir(), 'email-attachment-');
        file_put_contents($file, 'attachment body');

        try {
            $email = new Email();
            $payload = $this->invokeEmailMethod($email, 'attachmentPayload', [[
                'file' => $file,
                'type' => 'text/plain',
            ]]);

            $this->assertSame('text/plain', $payload['type']);
            $this->assertSame(FileSystem::fileBasename($file), $payload['name']);
            $this->assertSame(chunk_split(base64_encode(FileSystem::fileContents($file))), $payload['contents']);
        } finally {
            if ($file && FileSystem::fileExists($file)) {
                unlink($file);
            }
        }
    }

    public function testAttachmentPayloadRejectsMissingFile()
    {
        $email = new Email();
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('missing-email-attachment-', true);

        $this->assertNull($this->invokeEmailMethod($email, 'attachmentPayload', [[
            'file' => $path,
            'type' => 'text/plain',
        ]]));
        $this->assertFileDoesNotExist($path);
    }

    private function invokeEmailMethod(Email $email, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionClass($email);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($email, $arguments);
    }

    private function emailProperty(Email $email, string $property)
    {
        $reflection = new \ReflectionClass($email);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($email);
    }
}
