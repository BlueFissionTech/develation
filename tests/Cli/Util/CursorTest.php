<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Cursor;
use BlueFission\Behavioral\Behaviors\Event;

class CursorTest extends \PHPUnit\Framework\TestCase
{
    public function testMoveFiresChangeEvent()
    {
        $cursor = new Cursor(1, 1);
        $changed = false;

        $cursor->when(new Event(Event::CHANGE), function () use (&$changed) {
            $changed = true;
        });

        $cursor->moveTo(4, 3);

        $this->assertTrue($changed);
        $this->assertSame(4, $cursor->x());
        $this->assertSame(3, $cursor->y());
    }

    public function testRenderPositionFiresSentEvent()
    {
        $cursor = new Cursor(2, 5);
        $sent = false;

        $cursor->when(new Event(Event::SENT), function () use (&$sent) {
            $sent = true;
        });

        $output = $cursor->renderPosition();

        $this->assertSame("\033[5;2H", $output);
        $this->assertTrue($sent);
    }
}
