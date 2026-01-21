<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Val;
use BlueFission\Cli\Util\Tty;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

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
        $ansi = new self();
        return $ansi->supportsColorsCheck($isTty);
    }

    public static function colorize(string $text, ?string $color = null, array $styles = [], ?bool $force = null): string
    {
        $ansi = new self();
        return $ansi->colorizeText($text, $color, $styles, $force);
    }

    public static function dim(string $text, ?bool $force = null): string
    {
        $ansi = new self();
        return $ansi->colorizeText($text, null, ['dim'], $force);
    }

    public static function gray(string $text, ?bool $force = null): string
    {
        $ansi = new self();
        return $ansi->colorizeText($text, 'gray', [], $force);
    }

    public static function strip(string $text): string
    {
        $ansi = new self();
        return $ansi->stripText($text);
    }

    public function supportsColorsCheck(?bool $isTty = null): bool
    {
        $isTty = Dev::apply('_in', $isTty);
        Dev::do('_before', [$isTty, $this]);

        if (Val::isNull($isTty)) {
            $isTty = Tty::isTty();
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $supported = getenv('ANSICON')
                || getenv('WT_SESSION')
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        } else {
            $supported = true;
        }

        $result = (bool)$isTty && (bool)$supported;
        $result = (bool)Dev::apply('_out', $result);
        $this->trigger(Event::PROCESSED, new Meta(data: $result));
        Dev::do('_after', [$result, $this]);

        return $result;
    }

    public function colorizeText(string $text, ?string $color = null, array $styles = [], ?bool $force = null): string
    {
        $text = Dev::apply('_in', $text);
        $color = Dev::apply('_in', $color);
        $styles = Dev::apply('_in', $styles);
        $force = Dev::apply('_in', $force);
        Dev::do('_before', [$text, $color, $styles, $force, $this]);

        if ($force === false) {
            return (string)Dev::apply('_out', $text);
        }

        if ($force !== true && !$this->supportsColorsCheck()) {
            return (string)Dev::apply('_out', $text);
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
            return (string)Dev::apply('_out', $text);
        }

        $output = "\033[" . implode(';', $codes) . "m" . $text . self::RESET;
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);

        return $output;
    }

    public function stripText(string $text): string
    {
        $text = Dev::apply('_in', $text);
        Dev::do('_before', [$text, $this]);
        $output = preg_replace('/\e\[[0-9;]*m/', '', $text);
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);

        return $output;
    }
}
