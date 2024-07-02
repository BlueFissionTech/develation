<?php
namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
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
}
