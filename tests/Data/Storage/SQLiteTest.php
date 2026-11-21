<?php

namespace BlueFission\Tests;

use BlueFission\Data\Storage\SQLite;
use BlueFission\Connections\Database\SQLiteBlob;

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

        $this->assertSame(SQLite::STATUS_SUCCESS, $storage->status());
        $this->assertStringContainsString('SELECT', $storage->query());
        $this->assertStringContainsString('items', $storage->query());
        $this->assertCount(3, $rows);
        $this->assertSame(['one', 'two', 'three'], array_column($rows, 'name'));
        $this->assertSame('one', $storage->field('name'));
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

    public function testExplicitBlobValueSurvivesStorageWriteAndReopen(): void
    {
        $db = new \SQLite3($this->_database);
        $this->assertTrue($db->exec('CREATE TABLE bytes (id INTEGER PRIMARY KEY AUTOINCREMENT, payload BLOB, label TEXT)'));
        $db->close();

        $storage = new SQLite(['location' => $this->_database, 'name' => 'bytes']);
        $storage->activate();
        $storage->field('payload', new SQLiteBlob("A\0B\xff"));
        $storage->field('label', 'plain');
        $storage->write();

        $this->assertSame(SQLite::STATUS_SUCCESS, $storage->status());
        $db = new \SQLite3($this->_database);
        $row = $db->querySingle('SELECT payload, label, typeof(payload) AS payload_type, typeof(label) AS label_type FROM bytes', true);
        $db->close();
        $this->assertSame(bin2hex("A\0B\xff"), bin2hex($row['payload']));
        $this->assertSame('blob', $row['payload_type']);
        $this->assertSame('plain', $row['label']);
        $this->assertSame('text', $row['label_type']);

        $reader = new SQLite(['location' => $this->_database, 'name' => 'bytes']);
        $reader->activate()->readAll();
        $rows = $reader->fetchRows();
        $this->assertCount(1, $rows);
        $this->assertSame(bin2hex("A\0B\xff"), bin2hex($rows[0]['payload']));
        $this->assertSame(bin2hex("A\0B\xff"), bin2hex($reader->field('payload')));
    }

    public function testBlobStorageHandlesEmptyLargeAndUpdatedValues(): void
    {
        $db = new \SQLite3($this->_database);
        $this->assertTrue($db->exec('CREATE TABLE bytes (id INTEGER PRIMARY KEY AUTOINCREMENT, payload BLOB, label TEXT)'));
        $db->close();

        foreach (['', 'plain', "\x00\xff\x80", str_repeat("\x00\xff", 4096)] as $bytes) {
            $storage = new SQLite(['location' => $this->_database, 'name' => 'bytes']);
            $storage->activate();
            $storage->field('payload', new SQLiteBlob($bytes));
            $storage->field('label', 'ordinary');
            $storage->write();
            $this->assertSame(SQLite::STATUS_SUCCESS, $storage->status());
        }

        $db = new \SQLite3($this->_database);
        $result = $db->query('SELECT id, payload, typeof(payload) AS payload_type FROM bytes ORDER BY id');
        $firstId = null;
        foreach (['', 'plain', "\x00\xff\x80", str_repeat("\x00\xff", 4096)] as $bytes) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $firstId ??= $row['id'];
            $this->assertSame(bin2hex($bytes), bin2hex($row['payload']));
            $this->assertSame('blob', $row['payload_type']);
        }
        $result->finalize();
        $db->close();

        $storage = new SQLite(['location' => $this->_database, 'name' => 'bytes']);
        $storage->activate();
        $storage->field('id', $firstId);
        $storage->field('payload', new SQLiteBlob("updated\0\xff"));
        $storage->field('label', 'ordinary');
        $storage->write();
        $this->assertSame(SQLite::STATUS_SUCCESS, $storage->status());
        $db = new \SQLite3($this->_database);
        $row = $db->querySingle('SELECT payload, typeof(payload) AS payload_type FROM bytes WHERE id = ' . (int)$firstId, true);
        $this->assertSame(bin2hex("updated\0\xff"), bin2hex($row['payload']));
        $this->assertSame('blob', $row['payload_type']);
        $db->close();
    }

    public function testFailedBlobStorageWriteCannotReportSuccess(): void
    {
        $db = new \SQLite3($this->_database);
        $this->assertTrue($db->exec('CREATE TABLE bytes (id INTEGER PRIMARY KEY AUTOINCREMENT, payload BLOB, label TEXT UNIQUE)'));
        $db->close();

        foreach (['same', 'same'] as $label) {
            $storage = new SQLite(['location' => $this->_database, 'name' => 'bytes']);
            $storage->activate();
            $storage->field('payload', new SQLiteBlob("A\0B"));
            $storage->field('label', $label);
            $storage->write();
        }
        $this->assertNotSame(SQLite::STATUS_SUCCESS, $storage->status());
        $db = new \SQLite3($this->_database);
        $this->assertSame(1, $db->querySingle('SELECT COUNT(*) FROM bytes'));
        $db->close();
    }
}
