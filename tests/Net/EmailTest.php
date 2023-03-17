<?php
namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\Email;

class EmailTest extends TestCase
{
    public function testConstructor()
    {
        $email = new Email('test@example.com', 'test@example.com', 'Test Subject', 'Test Message', 'cc@example.com', 'bcc@example.com', true, 'Test Headers', 'Test Additional');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame(array('test@example.com'), $email->field('to'));
        $this->assertSame(array('cc@example.com'), $email->field('cc'));
        $this->assertSame(array('bcc@example.com'), $email->field('bcc'));
        $this->assertSame('test@example.com', $email->field('from'));
        $this->assertSame('Test Subject', $email->field('subject'));
        $this->assertSame('Test Message', $email->field('message'));
        $this->assertSame(array('Test Headers'), $email->headers());
    }

    public function testField()
    {
        $email = new Email();

        $this->assertNull($email->field('invalid_field'));
        $this->assertSame('Test Subject', $email->field('subject', 'Test Subject'));
        $this->assertSame('Test Subject', $email->field('subject'));
    }

    public function testHeaders()
    {
        $email = new Email();

        $this->assertSame(array(), $email->headers());
        $this->assertNull($email->headers('invalid_header'));
        $this->assertSame('Test Header', $email->headers('test_header', 'Test Header'));
        $this->assertSame('Test Header', $email->headers('test_header'));
    }
}
