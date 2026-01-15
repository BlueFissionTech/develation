<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Val;

class Ansi extends Obj
{
    const RESET = "\033[0m";

    protected static array $foreground = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37,
        'gray' => 90,
        'bright_red' => 91,
        'bright_green' => 92,
        'bright_yellow' => 93,
        'bright_blue' => 94,
        'bright_magenta' => 95,
        'bright_cyan' => 96,
        'bright_white' => 97,
    ];

    protected static array $styles = [
        'bold' => 1,
        'dim' => 2,
        'italic' => 3,
        'underline' => 4,
        'reverse' => 7,
    ];

    public static function supportsColors(?bool $isTty = null): bool
    {
        if (Val::isNull($isTty)) {
            if (function_exists('stream_isatty')) {
                $isTty = @stream_isatty(STDOUT);
            } elseif (function_exists('posix_isatty')) {
                $isTty = @posix_isatty(STDOUT);
            } else {
                $isTty = false;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $supported = getenv('ANSICON')
                || getenv('WT_SESSION')
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        } else {
            $supported = true;
        }

        return (bool)$isTty && (bool)$supported;
    }

    public static function colorize(string $text, ?string $color = null, array $styles = [], ?bool $force = null): string
    {
        if ($force === false) {
            return $text;
        }

        if ($force !== true && !self::supportsColors()) {
            return $text;
        }

        $codes = [];

        if (Val::isNotNull($color) && isset(self::$foreground[$color])) {
            $codes[] = self::$foreground[$color];
        }

        foreach ($styles as $style) {
            if (isset(self::$styles[$style])) {
                $codes[] = self::$styles[$style];
            }
        }

        if (!$codes) {
            return $text;
        }

        return "\033[" . implode(';', $codes) . "m" . $text . self::RESET;
    }

    public static function dim(string $text, ?bool $force = null): string
    {
        return self::colorize($text, null, ['dim'], $force);
    }

    public static function gray(string $text, ?bool $force = null): string
    {
        return self::colorize($text, 'gray', [], $force);
    }

    public static function strip(string $text): string
    {
        return preg_replace('/\e\[[0-9;]*m/', '', $text);
    }
}
