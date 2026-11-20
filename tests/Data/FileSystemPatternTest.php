<?php

namespace BlueFission\Tests\Data;

use BlueFission\Data\FileSystem;
use BlueFission\Tests\Support\TestEnvironment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class FileSystemPatternTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = TestEnvironment::tempDir('bf_file_pattern');
    }

    protected function tearDown(): void
    {
        TestEnvironment::removeDir($this->directory);
    }

    public function testMatchesOnlyRegularFilesInOneDirectoryInBinaryOrder(): void
    {
        foreach (['b.txt', 'a.txt', 'uppercase.TXT', '.hidden.txt', 'note.log'] as $name) {
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $name, $name);
        }
        mkdir($this->directory . DIRECTORY_SEPARATOR . 'directory.txt');
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'directory.txt' . DIRECTORY_SEPARATOR . 'nested.txt', 'nested');

        $before = scandir($this->directory);
        $this->assertSame(['.hidden.txt', 'a.txt', 'b.txt'],
            FileSystem::matchingFiles($this->directory, '*.txt'));
        $this->assertSame(['uppercase.TXT'], FileSystem::matchingFiles($this->directory, '*.TXT'));
        $this->assertSame([], FileSystem::matchingFiles($this->directory, '*.csv'));
        $this->assertSame($before, scandir($this->directory));
    }

    public function testQuestionMarkMatchesOneUnicodeCharacter(): void
    {
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . "\xc3\xa9.txt", 'unicode');
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'ab.txt', 'two');
        $this->assertSame(["\xc3\xa9.txt"], FileSystem::matchingFiles($this->directory, '?.txt'));
    }

    public function testExcludesSymlinksWhenTheHostCanCreateThem(): void
    {
        $target = $this->directory . DIRECTORY_SEPARATOR . 'real.txt';
        $link = $this->directory . DIRECTORY_SEPARATOR . 'linked.txt';
        file_put_contents($target, 'real');
        if (@symlink($target, $link)) {
            $this->assertSame(['real.txt'], FileSystem::matchingFiles($this->directory, '*.txt'));
        } else {
            $this->markTestSkipped('Symlink creation is unavailable on this host.');
        }
    }

    public function testRejectsTraversalAndMalformedPatterns(): void
    {
        foreach (['', '../*.txt', '..', 'nested/*.txt', 'nested\\*.txt', 'file[0-9].txt', "\xff"] as $pattern) {
            try {
                FileSystem::matchingFiles($this->directory, $pattern);
                $this->fail('Invalid pattern was accepted.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testDistinguishesMissingAndNonDirectoryTargetsFromEmptyMatches(): void
    {
        $missing = $this->directory . DIRECTORY_SEPARATOR . 'missing';
        try {
            FileSystem::matchingFiles($missing, '*');
            $this->fail('Missing directory was accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertDirectoryDoesNotExist($missing);
        }

        $file = $this->directory . DIRECTORY_SEPARATOR . 'file.txt';
        file_put_contents($file, 'x');
        try {
            FileSystem::matchingFiles($file, '*');
            $this->fail('File target was accepted as a directory.');
        } catch (\UnexpectedValueException $exception) {
            $this->assertFileExists($file);
        }
        $this->assertSame([], FileSystem::matchingFiles($this->directory, '*.csv'));

        $this->expectException(\InvalidArgumentException::class);
        FileSystem::matchingFiles('php://memory', '*');
    }
}
