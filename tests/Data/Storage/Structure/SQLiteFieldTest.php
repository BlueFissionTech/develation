<?php
namespace BlueFission\Tests;

use BlueFission\Data\Storage\Structure\SQLiteField;

class SQLiteFieldTest extends \PHPUnit\Framework\TestCase
{
    public function testAutoincrementDefinitionUsesPrimaryKey()
    {
        $field = new SQLiteField('id');
        $field->type('numeric')->primary()->autoincrement();

        $definition = $field->definition();
        $this->assertStringContainsString('PRIMARY KEY AUTOINCREMENT', $definition);
        $this->assertSame('', $field->extras());
    }

    public function testNumericDefaultDefinitionDoesNotRequireOpenConnection()
    {
        $field = new SQLiteField('enabled');
        $field->type('numeric')->default(1);

        $definition = $field->definition();

        $this->assertStringContainsString('DEFAULT 1', $definition);
    }

    public function testTextDefaultDefinitionIsSafelyQuotedWithoutOpenConnection()
    {
        $field = new SQLiteField('name');
        $field->type('text')->default('blue');

        $definition = $field->definition();

        $this->assertStringContainsString("DEFAULT 'blue'", $definition);
    }

    public function testExplicitBlobTypeDoesNotChangeTextDefaults()
    {
        $blob = new SQLiteField('payload');
        $text = new SQLiteField('label');
        $default = new SQLiteField('note');

        $this->assertSame('`payload` BLOB NOT NULL', $blob->type('blob')->definition());
        $this->assertSame('`label` TEXT NOT NULL', $text->type('text')->definition());
        $this->assertSame('`note` TEXT NOT NULL', $default->definition());
    }

    public function testExplicitBlobDefinitionCreatesBlobColumn()
    {
        if (!class_exists(\SQLite3::class)) {
            $this->markTestSkipped('SQLite3 is not available.');
        }

        $field = new SQLiteField('payload');
        $database = new \SQLite3(':memory:');
        try {
            $this->assertTrue($database->exec('CREATE TABLE sample (' . $field->type('blob')->definition() . ')'));
            $column = $database->querySingle('SELECT type FROM pragma_table_info(\'sample\') WHERE name = \'payload\'');
            $this->assertSame('BLOB', $column);
        } finally {
            $database->close();
        }
    }
}
