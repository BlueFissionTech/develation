<?php

namespace BlueFission\Tests\Data;

use BlueFission\Data\File;
use BlueFission\Data\FileSystem;
use BlueFission\Tests\Support\TestEnvironment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/TestEnvironment.php';

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

    public function testPathBoundFileReplacesLiteralBytesWithoutChangingLegacyWrites(): void
    {
        $directory = TestEnvironment::tempDir('bf_file_bytes');
        $path = $directory . DIRECTORY_SEPARATOR . 'report with spaces.txt';
        try {
            $file = File::atPath($path, $directory);
            $this->assertFalse($file->exists());

            foreach (["Keep C:\\data\\vectors literal.\n", "A\0B\xff", '0', ''] as $bytes) {
                $file->contents($bytes)->write();
                $this->assertSame($bytes, file_get_contents($path));
                $this->assertSame($bytes, $file->read()->contents());
            }

            $file->contents('longer')->write()->write();
            $this->assertSame('longer', file_get_contents($path));
            $file->contents('x')->write();
            $this->assertSame('x', file_get_contents($path));
        } finally {
            TestEnvironment::removeDir($directory);
        }
    }

    public function testExplicitByteReplacementRejectsMissingParentAndRootEscape(): void
    {
        $directory = TestEnvironment::tempDir('bf_file_root');
        $outside = TestEnvironment::tempDir('bf_file_outside');
        try {
            $missing = $directory . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'report.txt';
            $this->assertFalse(File::atPath($missing, $directory)->exists());
            try {
                FileSystem::replaceBytesAtPath($missing, 'new', $directory);
                $this->fail('A write must not create parent directories by default.');
            } catch (\RuntimeException $exception) {
                $this->assertFileDoesNotExist($missing);
            }

            $escaped = $outside . DIRECTORY_SEPARATOR . 'outside.txt';
            try {
                FileSystem::replaceBytesAtPath($escaped, 'no', $directory);
                $this->fail('A configured root must reject writes outside it.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertFileDoesNotExist($escaped);
            }
        } finally {
            TestEnvironment::removeDir($directory);
            TestEnvironment::removeDir($outside);
        }
    }

    public function testFailedBoundReadPreservesBufferAndUnsetWriteDoesNotCreateTarget(): void
    {
        $directory = TestEnvironment::tempDir('bf_file_fail');
        $path = $directory . DIRECTORY_SEPARATOR . 'new.txt';
        try {
            $file = File::atPath($path, $directory)->contents('keep');
            try {
                $file->read();
                $this->fail('Missing file reads must fail.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('keep', $file->contents());
            }
            try {
                File::atPath($path, $directory)->write();
                $this->fail('Unset bytes must not become an empty replacement.');
            } catch (\LogicException $exception) {
                $this->assertFileDoesNotExist($path);
            }
        } finally {
            TestEnvironment::removeDir($directory);
        }
    }

    public function testByteReplacementRejectsNonLocalAndNonRegularTargets(): void
    {
        $directory = TestEnvironment::tempDir('bf_file_target');
        try {
            $child = $directory . DIRECTORY_SEPARATOR . 'child';
            mkdir($child);
            foreach (['php://memory', 'relative.txt', "bad\0path"] as $invalid) {
                try {
                    FileSystem::replaceBytesAtPath($invalid, 'new', $directory);
                    $this->fail('Invalid paths must fail.');
                } catch (\InvalidArgumentException $exception) {
                    $this->addToAssertionCount(1);
                }
            }
            try {
                FileSystem::replaceBytesAtPath($child, 'new', $directory);
                $this->fail('A directory cannot be replaced as a file.');
            } catch (\UnexpectedValueException $exception) {
                $this->assertDirectoryExists($child);
            }
        } finally {
            TestEnvironment::removeDir($directory);
        }
    }
}
