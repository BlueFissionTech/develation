<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Spinner;
use BlueFission\Behavioral\Behaviors\Event;

class SpinnerTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderSpinner()
    {
        $spinner = new Spinner('Loading', ['.', 'o'], 100);
        $this->assertSame('Loading .', $spinner->render());
    }

    public function testAdvanceChangesFrame()
    {
        $spinner = new Spinner('', ['.', 'o'], 100);
        $first = $spinner->frame();
        $spinner->advance();
        $second = $spinner->frame();

        $this->assertNotSame($first, $second);
    }

    public function testAdvanceFiresChangeEvent()
    {
        $spinner = new Spinner('Work', ['|', '/'], 100);
        $changed = false;

        $spinner->when(new Event(Event::CHANGE), function () use (&$changed) {
            $changed = true;
        });

        $spinner->advance();

        $this->assertTrue($changed);
    }
}
