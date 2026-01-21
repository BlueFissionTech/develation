<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Screen extends Obj
{
    public static function clearLine(): string
    {
        $screen = new self();
        return $screen->clearLineOutput();
    }

    public static function clearScreen(): string
    {
        $screen = new self();
        return $screen->clearScreenOutput();
    }

    public static function moveCursor(int $x, int $y): string
    {
        $screen = new self();
        return $screen->moveCursorOutput($x, $y);
    }

    public static function saveCursor(): string
    {
        $screen = new self();
        return $screen->saveCursorOutput();
    }

    public static function restoreCursor(): string
    {
        $screen = new self();
        return $screen->restoreCursorOutput();
    }

    public static function hideCursor(): string
    {
        $screen = new self();
        return $screen->hideCursorOutput();
    }

    public static function showCursor(): string
    {
        $screen = new self();
        return $screen->showCursorOutput();
    }

    public static function rewriteLine(string $text): string
    {
        $screen = new self();
        return $screen->rewriteLineOutput($text);
    }

    public function clearLineOutput(): string
    {
        Dev::do('_before', [$this]);
        $output = "\033[2K";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function clearScreenOutput(): string
    {
        Dev::do('_before', [$this]);
        $output = "\033[2J\033[H";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function moveCursorOutput(int $x, int $y): string
    {
        $x = Dev::apply('_in', $x);
        $y = Dev::apply('_in', $y);
        Dev::do('_before', [$x, $y, $this]);
        $output = "\033[" . $y . ";" . $x . "H";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function saveCursorOutput(): string
    {
        Dev::do('_before', [$this]);
        $output = "\033[s";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function restoreCursorOutput(): string
    {
        Dev::do('_before', [$this]);
        $output = "\033[u";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function hideCursorOutput(): string
    {
        Dev::do('_before', [$this]);
        $output = "\033[?25l";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function showCursorOutput(): string
    {
        Dev::do('_before', [$this]);
        $output = "\033[?25h";
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function rewriteLineOutput(string $text): string
    {
        $text = Dev::apply('_in', $text);
        Dev::do('_before', [$text, $this]);
        $output = "\r" . $this->clearLineOutput() . $text;
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }
}
