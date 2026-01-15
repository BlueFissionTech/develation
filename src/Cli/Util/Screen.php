<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;

class Screen extends Obj
{
    public static function clearLine(): string
    {
        return "\033[2K";
    }

    public static function clearScreen(): string
    {
        return "\033[2J\033[H";
    }

    public static function moveCursor(int $x, int $y): string
    {
        return "\033[" . $y . ";" . $x . "H";
    }

    public static function saveCursor(): string
    {
        return "\033[s";
    }

    public static function restoreCursor(): string
    {
        return "\033[u";
    }

    public static function hideCursor(): string
    {
        return "\033[?25l";
    }

    public static function showCursor(): string
    {
        return "\033[?25h";
    }

    public static function rewriteLine(string $text): string
    {
        return "\r" . self::clearLine() . $text;
    }
}
