<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Table;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderTable()
    {
        $headers = ['Name', 'Age'];
        $rows = [
            ['Ada', 39],
            ['Bob', 7],
        ];

        $expected = implode(PHP_EOL, [
            '+------+-----+',
            '| Name | Age |',
            '+------+-----+',
            '| Ada  | 39  |',
            '| Bob  | 7   |',
            '+------+-----+',
        ]);

        $this->assertSame($expected, Table::render($headers, $rows));
    }
}
