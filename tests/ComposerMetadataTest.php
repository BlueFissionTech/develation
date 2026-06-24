<?php

namespace BlueFission\Tests;

use PHPUnit\Framework\TestCase;

class ComposerMetadataTest extends TestCase
{
    public function testRatchetTransportIsOptional(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);

        $this->assertArrayNotHasKey('cboden/ratchet', $composer['require'] ?? []);
        $this->assertArrayHasKey('cboden/ratchet', $composer['require-dev'] ?? []);
        $this->assertArrayHasKey('cboden/ratchet', $composer['suggest'] ?? []);
    }
}
