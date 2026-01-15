<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\ProgressBar;
use BlueFission\Behavioral\Behaviors\Event;

class ProgressBarTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderProgress()
    {
        $bar = new ProgressBar(10, 10, '#', '-');
        $bar->setCurrent(4);

        $this->assertSame('[####------]  40% (4/10)', $bar->render());
    }

    public function testRenderZeroTotal()
    {
        $bar = new ProgressBar(0, 5);
        $this->assertSame('[-----]   0% (0/0)', $bar->render());
    }

    public function testUpdateFiresChangeEvent()
    {
        $bar = new ProgressBar(5, 5);
        $changed = false;

        $bar->when(new Event(Event::CHANGE), function () use (&$changed) {
            $changed = true;
        });

        $bar->setCurrent(3);

        $this->assertTrue($changed);
    }
}
