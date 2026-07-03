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
}
