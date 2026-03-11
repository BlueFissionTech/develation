<?php

namespace BlueFission\Tests;

use BlueFission\Data\Storage\SQLite;

class SQLiteAutoCreateTest extends \PHPUnit\Framework\TestCase
{
    private string $_database;

    protected function setUp(): void
    {
        if (!class_exists('SQLite3')) {
            $this->markTestSkipped('SQLite3 extension is not available.');
        }

        $this->_database = tempnam(sys_get_temp_dir(), 'bf_sqlite_auto_');
    }

    protected function tearDown(): void
    {
        if (isset($this->_database) && file_exists($this->_database)) {
            @unlink($this->_database);
        }
    }

    public function testCreateInfersTextForOrdinaryStrings()
    {
        $storage = new SQLite([
            'location' => $this->_database,
            'name' => 'test_records',
        ]);

        $storage->activate();

        $ref = new \ReflectionClass($storage);
        $data = $ref->getProperty('_data');
        $data->setAccessible(true);
        $data->setValue($storage, [
            'name' => 'alpha',
            'status' => 'active',
        ]);

        $create = $ref->getMethod('create');
        $create->setAccessible(true);
        $create->invoke($storage);

        $this->assertSame(SQLite::STATUS_SUCCESS, $storage->status());
        $this->assertStringContainsString('`name` TEXT', $storage->query());
        $this->assertStringNotContainsString('DATETIME', $storage->query());

        $db = new \SQLite3($this->_database);
        $result = $db->query("PRAGMA table_info(`test_records`)");
        $fields = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $fields[$row['name']] = $row;
        }
        $db->close();

        $this->assertArrayHasKey('name', $fields);
        $this->assertSame('TEXT', strtoupper($fields['name']['type']));
    }
}
