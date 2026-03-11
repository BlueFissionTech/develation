<?php

namespace BlueFission\Tests;

use BlueFission\Data\Storage\SQLite;

class SQLiteTest extends \PHPUnit\Framework\TestCase
{
    private string $_database;

    protected function setUp(): void
    {
        if (!class_exists('SQLite3')) {
            $this->markTestSkipped('SQLite3 extension is not available.');
        }

        $this->_database = tempnam(sys_get_temp_dir(), 'bf_sqlite_test_');
        $db = new \SQLite3($this->_database);
        $db->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $db->exec("INSERT INTO items (name) VALUES ('one')");
        $db->exec("INSERT INTO items (name) VALUES ('two')");
        $db->exec("INSERT INTO items (name) VALUES ('three')");
        $db->close();
    }

    protected function tearDown(): void
    {
        if (isset($this->_database) && file_exists($this->_database)) {
            @unlink($this->_database);
        }
    }

    public function testDefaultReadMaintainsSingleRowBehavior()
    {
        $storage = new SQLite([
            'location' => $this->_database,
            'name' => 'items',
        ]);

        $storage->activate()->read();

        $this->assertCount(1, $storage->fetchRows());
    }

    public function testReadAllFetchesAllRows()
    {
        $storage = new SQLite([
            'location' => $this->_database,
            'name' => 'items',
        ]);

        $storage->activate()->readAll();
        $rows = $storage->fetchRows();

        $this->assertCount(3, $rows);
        $this->assertSame(['one', 'two', 'three'], array_column($rows, 'name'));
    }

    public function testAllReturnsAllRowsDirectly()
    {
        $storage = new SQLite([
            'location' => $this->_database,
            'name' => 'items',
        ]);

        $rows = $storage->activate()->all();

        $this->assertCount(3, $rows);
    }
}
