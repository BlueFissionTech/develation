<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Canvas;
use BlueFission\Cli\Util\Screen;

class CanvasTest extends \PHPUnit\Framework\TestCase
{
    public function testRender()
    {
        $canvas = new Canvas(3, 2, '.');
        $canvas->set(2, 1, 'X');

        $expected = implode(PHP_EOL, [
            '.X.',
            '...',
        ]);

        $this->assertSame($expected, $canvas->render());
    }

    public function testRenderDiff()
    {
        $previous = new Canvas(3, 2, '.');
        $canvas = new Canvas(3, 2, '.');
        $canvas->set(2, 1, 'X');

        $output = $canvas->renderDiff($previous);

        $this->assertStringContainsString(Screen::moveCursor(1, 1), $output);
        $this->assertStringContainsString('.X.', $output);
    }
}
