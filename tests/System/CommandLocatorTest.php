<?php

namespace BlueFission\Tests\System;

use BlueFission\Data\FileSystem;
use BlueFission\System\CommandLocator;
use PHPUnit\Framework\TestCase;

class CommandLocatorTest extends TestCase
{
    public function testFindReturnsAbsoluteFilePath()
    {
        $file = tempnam(sys_get_temp_dir(), 'command-locator-');

        try {
            $this->assertSame(realpath($file), CommandLocator::find($file, [
                'cache' => false,
                'use_shell' => false,
            ]));
        } finally {
            if ($file && FileSystem::fileExists($file)) {
                unlink($file);
            }
        }
    }

    public function testFindReturnsNullForMissingAbsolutePath()
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('missing-command-', true);

        $this->assertNull(CommandLocator::find($path, [
            'cache' => false,
            'use_shell' => false,
        ]));
    }

    public function testFindPreservesAbsoluteSearchPathRoots()
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('command-locator-dir-', true);
        $command = 'bf-command-locator.test';
        $file = $directory . DIRECTORY_SEPARATOR . $command;

        mkdir($directory);
        file_put_contents($file, 'test');

        try {
            $this->assertSame(realpath($file), CommandLocator::find($command, [
                'paths' => [$directory],
                'env_path' => '',
                'cache' => false,
                'use_shell' => false,
            ]));
        } finally {
            if (FileSystem::fileExists($file)) {
                unlink($file);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
