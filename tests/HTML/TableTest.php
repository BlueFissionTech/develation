<?php

use BlueFission\HTML\Table;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigurable()
    {
        $config = [
            'columns' => '2',
            'headers' => ["Header1", "Header2"],
            'link_style' => '1',
        ];
        $table = new Table($config);
        $this->assertEquals($table->config("columns"), 2);
        $this->assertEquals($table->config("headers"), ["Header1", "Header2"]);
        $this->assertEquals($table->config("link_style"), 1);
    }

    public function testContent()
    {
        $table = new Table();
        $content = [
            ["Row1 Column1", "Row1 Column2"],
            ["Row2 Column1", "Row2 Column2"],
        ];
        $table->content($content);
        $this->assertEquals($table->content(), $content);
    }

    public function testRender()
    {
        $config = [
            'columns' => '2',
            'headers' => ["Header1", "Header2"],
            'link_style' => null,
        ];
        $content = [
            ["Row1 Column1", "Row1 Column2"],
            ["Row2 Column1", "Row2 Column2"],
        ];
        $table = new Table($config);
        $table->content($content);

        $expected_render = '<table class="dev_table" id="anyid">'
            . '<tr>'
                . '<th>'
                . 'Header1'
                . '</th>'
                . '<th>'
                . 'Header2'
                . '</th>'
            . '</tr>'
            . '<tr>'
                . '<td>'
                // . '<a href="#Row1 Column1">'
                . 'Row1 Column1'
                // . '</a>'
                . '</td>'
                . '<td>'
                // . '<a href="#Row1 Column2">'
                . 'Row1 Column2'
                // . '</a>'
                . '</td>'
            . '</tr>'
            . '<tr>'
                . '<td>'
                // . '<a href="#Row2 Column1">'
                . 'Row2 Column1'
                // . '</a>'
                . '</td>'
                . '<td>'
                // . '<a href="#Row2 Column2">'
                . 'Row2 Column2'
                // . '</a>'
                . '</td>'
            . '</tr>'
            . '</table>';

        $this->assertEquals($table->render(), $expected_render);
    }

    public function testRenderLinksUseHttpQueryHelper()
    {
        $table = new Table([
            'columns' => '2',
            'headers' => false,
            'href' => '/items',
            'link_style' => 1,
            'varname' => 'id',
            'value' => '42',
            'query' => ['token' => 'abc 123'],
        ]);

        $table->content([
            ['ignored', 'Details'],
        ]);

        $this->assertStringContainsString(
            'token=abc+123',
            $table->render()
        );
    }

    public function testRenderFormLinksIncludeQueryFields()
    {
        $table = new Table([
            'columns' => '2',
            'headers' => false,
            'href' => '/items',
            'link_style' => 2,
            'query' => ['token' => 'abc 123'],
        ]);

        $table->content([
            ['id' => 42, 'label' => 'Details'],
        ]);

        $result = $table->render();

        $this->assertStringContainsString('name="token"', $result);
        $this->assertStringContainsString('value="abc 123"', $result);
    }
}
