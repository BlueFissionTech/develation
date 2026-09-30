<?php

namespace BlueFission\Tests\Data\Storage\Structure;

use BlueFission\Connections\Database\SQLiteLink;
use BlueFission\Data\Storage\Structure\SQLiteScaffold;
use BlueFission\Data\Storage\Structure\SQLiteStructure;
use BlueFission\Tests\Support\TestEnvironment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Support/TestEnvironment.php';

class SQLiteScaffoldTest extends TestCase
{
    private array $links = [];
    private array $tempDirectories = [];

    protected function setUp(): void
    {
        if (!class_exists('SQLite3')) {
            $this->markTestSkipped('SQLiteScaffold tests require the sqlite3 extension');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->links as $link) {
            $link->close();
        }

        foreach ($this->tempDirectories as $directory) {
            TestEnvironment::removeDir($directory);
        }
    }

    public function testBorrowedConnectionSelectsTargetAndRemainsOpen(): void
    {
        $directory = TestEnvironment::tempDir('sqlite_scaffold');
        $this->tempDirectories[] = $directory;
        $target = $directory . DIRECTORY_SEPARATOR . 'target.sqlite';
        $other = $directory . DIRECTORY_SEPARATOR . 'other.sqlite';
        $link = $this->linkFor($target);
        $otherLink = $this->linkFor($other);
        $connection = $link->connection();

        ob_start();
        try {
            SQLiteScaffold::create('fixture', function (SQLiteStructure $structure): void {
                $structure->text('label');
            }, $link);
            $this->assertTrue(SQLiteLink::tableExists('fixture', $target));
            $this->assertFalse(SQLiteLink::tableExists('fixture', $other));

            SQLiteScaffold::delete('fixture', $link);
            $this->assertFalse(SQLiteLink::tableExists('fixture', $target));
            $this->assertSame($connection, $link->connection());
            $this->assertSame(SQLiteLink::STATUS_SUCCESS, $link->query('SELECT 1')->status());
            $this->assertSame(SQLiteLink::STATUS_CONNECTED, $otherLink->status());
        } finally {
            ob_end_clean();
        }
    }

    public function testBorrowedConnectionDoesNotCommitHostTransaction(): void
    {
        $directory = TestEnvironment::tempDir('sqlite_scaffold');
        $this->tempDirectories[] = $directory;
        $link = $this->linkFor($directory . DIRECTORY_SEPARATOR . 'transaction.sqlite');
        $connection = $link->connection();

        $this->assertTrue($connection->exec('BEGIN'));
        ob_start();
        try {
            SQLiteScaffold::create('fixture', function (SQLiteStructure $structure): void {
                $structure->text('label');
            }, $link);
        } finally {
            ob_end_clean();
        }

        $this->assertTrue($connection->exec('ROLLBACK'));
        $this->assertSame($connection, $link->connection());
        $this->assertFalse(SQLiteLink::tableExists('fixture', $directory . DIRECTORY_SEPARATOR . 'transaction.sqlite'));
    }

    private function linkFor(string $database): SQLiteLink
    {
        $link = new SQLiteLink(['database' => $database]);
        $link->open();
        $this->links[] = $link;

        return $link;
    }
}
