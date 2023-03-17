<?php

use BlueFission\HTML\Table;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigurable()
    {
        $config = array(
            'columns' => '2',
            'headers' => array("Header1", "Header2"),
            'link_style' => '1',
        );
        $table = new Table($config);
        $this->assertEquals($table->config("columns"), 2);
        $this->assertEquals($table->config("headers"), array("Header1", "Header2"));
        $this->assertEquals($table->config("link_style"), 1);
    }

    public function testContent()
    {
        $table = new Table();
        $content = array(
            array("Row1 Column1", "Row1 Column2"),
            array("Row2 Column1", "Row2 Column2"),
        );
        $table->content($content);
        $this->assertEquals($table->content(), $content);
    }

    public function testRender()
    {
        $config = array(
            'columns' => '2',
            'headers' => array("Header1", "Header2"),
            'link_style' => '1',
        );
        $content = array(
            array("Row1 Column1", "Row1 Column2"),
            array("Row2 Column1", "Row2 Column2"),
        );
        $table = new Table($config);
        $table->content($content);

        $expected_render = '<table class="dev_table" id="anyid">'
            . '<tr>'
                . '<th>'
                . ''
                . '</th>'
                . '<th>'
                . 'Header1'
                . '</th>'
                . '<th>'
                . 'Header2'
                . '</th>'
            . '</tr>'
            . '<tr>'
                . '<td>'
                . '<a href="#Row1 Column1">'
                . 'Row1 Column1'
                . '</a>'
                . '</td>'
                . '<td>'
                . '<a href="#Row1 Column2">'
                . 'Row1 Column2'
                . '</a>'
                . '</td>'
            . '</tr>'
            . '<tr>'
                . '<td>'
                . '<a href="#Row2 Column1">'
                . 'Row2 Column1'
                . '</a>'
                . '</td>'
                . '<td>'
                . '<a href="#Row2 Column2">'
                . 'Row2 Column2'
                . '</a>'
                . '</td>'
            . '</tr>'
            . '</table>';

        $this->assertEquals($table->render(), $expected_render);
    }
}
