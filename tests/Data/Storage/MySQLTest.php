<?php

namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\MySQL;
use PHPUnit\Framework\TestCase;

class MySQLTest extends TestCase
{
    public function testIdReturnsNullWhenTableListIsEmpty(): void
    {
        $storage = new MySQL();

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $this->assertNull($storage->id());
        } finally {
            restore_error_handler();
        }
    }
}
