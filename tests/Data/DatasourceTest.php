<?php

namespace BlueFission\Tests\Data;

use BlueFission\Data\Datasource\Datasource;
use PHPUnit\Framework\TestCase;

class ConfiguredDatasourceProbe extends Datasource
{
    protected $_config = [
        'name' => '',
    ];
}

class DatasourceTest extends TestCase
{
    public function testConstructorKeepsConfigAndInitializesIndex(): void
    {
        $source = new ConfiguredDatasourceProbe(['name' => 'records']);

        $this->assertSame('records', $source->config('name'));
        $this->assertSame(-1, $source->index());
    }
}
