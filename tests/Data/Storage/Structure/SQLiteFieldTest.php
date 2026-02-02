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
}
