<?php

namespace BlueFission\Tests\Data;

use BlueFission\Data\File;
use PHPUnit\Framework\TestCase;

class FilePersistenceTest extends TestCase
{
    public function testUnboundWriteReportsMissingBackendWithoutDiscardingBuffer(): void
    {
        $file = (new File())->contents('first')->append(' second');

        try {
            $file->write();
            $this->fail('An unbound File must not report a successful write.');
        } catch (\LogicException $exception) {
            $this->assertSame('No storage backend is bound to this file.', $exception->getMessage());
        }

        $this->assertSame('first second', $file->contents());
    }

    public function testUnboundReadReportsMissingBackendWithoutChangingBuffer(): void
    {
        $file = (new File())->contents('unchanged');

        try {
            $file->read();
            $this->fail('An unbound File must not report a successful read.');
        } catch (\LogicException $exception) {
            $this->assertSame('No storage backend is bound to this file.', $exception->getMessage());
        }

        $this->assertSame('unchanged', $file->contents());
    }
}
