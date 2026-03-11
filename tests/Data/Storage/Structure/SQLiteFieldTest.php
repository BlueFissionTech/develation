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
}
