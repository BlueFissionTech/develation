<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\StatusBar;

class StatusBarTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderStatusBar()
    {
        $status = new StatusBar();
        $status->set('tick', '10');
        $status->set('mode', 'run');

        $this->assertSame('tick: 10 | mode: run', $status->render());
    }

    public function testRenderWithWidth()
    {
        $status = new StatusBar();
        $status->set('tick', '100');
        $status->set('mode', 'running');

        $output = $status->render(12);

        $this->assertSame(12, strlen($output));
    }
}
