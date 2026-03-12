<?php

namespace BlueFission\Tests\Connections\Database;

use BlueFission\Connections\Database\SQLiteLink;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../../Support/TestEnvironment.php';

class SQLiteLinkTest extends \PHPUnit\Framework\TestCase
{
    private array $links = [];
    private array $tempDirectories = [];

    protected function setUp(): void
    {
        if (!class_exists('SQLite3')) {
            $this->markTestSkipped('SQLiteLink tests require the sqlite3 extension');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->links as $link) {
            if ($link instanceof SQLiteLink) {
                $link->close();
            }
        }

        foreach ($this->tempDirectories as $directory) {
            TestEnvironment::removeDir($directory);
        }
    }

    public function testUsesDistinctConnectionsForDifferentDatabasePaths(): void
    {
        [$databaseA, $databaseB] = $this->databasePair();

        $linkA = $this->linkFor($databaseA);
        $linkB = $this->linkFor($databaseB);

        $this->assertNotSame($linkA->connection(), $linkB->connection());

        $this->assertTrue($linkA->connection()->exec('CREATE TABLE alpha (id INTEGER PRIMARY KEY, name TEXT)'));
        $this->assertTrue(SQLiteLink::tableExists('alpha', $databaseA));
        $this->assertFalse(SQLiteLink::tableExists('alpha', $databaseB));

        $this->assertTrue($linkB->connection()->exec('CREATE TABLE beta (id INTEGER PRIMARY KEY, body TEXT)'));
        $this->assertFalse(SQLiteLink::tableExists('beta', $databaseA));
        $this->assertTrue(SQLiteLink::tableExists('beta', $databaseB));
    }

    public function testSwitchesConnectionsWhenDatabaseChangesOnSameInstance(): void
    {
        [$databaseA, $databaseB] = $this->databasePair();

        $link = $this->linkFor($databaseA);
        $connectionA = $link->connection();

        $this->assertTrue($connectionA->exec('CREATE TABLE alpha (id INTEGER PRIMARY KEY, name TEXT)'));

        $link->database($databaseB);
        $link->open();

        $connectionB = $link->connection();

        $this->assertNotSame($connectionA, $connectionB);
        $this->assertTrue($connectionB->exec('CREATE TABLE beta (id INTEGER PRIMARY KEY, body TEXT)'));

        $this->assertTrue(SQLiteLink::tableExists('alpha', $databaseA));
        $this->assertFalse(SQLiteLink::tableExists('alpha', $databaseB));
        $this->assertFalse(SQLiteLink::tableExists('beta', $databaseA));
        $this->assertTrue(SQLiteLink::tableExists('beta', $databaseB));
    }

    private function linkFor(string $database): SQLiteLink
    {
        $link = new SQLiteLink(['database' => $database]);
        $link->open();
        $this->links[] = $link;

        return $link;
    }

    private function databasePair(): array
    {
        $directory = TestEnvironment::tempDir('sqlite_link');
        $this->tempDirectories[] = $directory;

        return [
            $directory . DIRECTORY_SEPARATOR . 'alpha.sqlite',
            $directory . DIRECTORY_SEPARATOR . 'beta.sqlite',
        ];
    }
}
