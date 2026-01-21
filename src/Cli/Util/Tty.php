<?php

namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Tty extends Obj
{
    public static function isTty($stream = null): bool
    {
        $tty = new self();
        return $tty->isStreamTty($stream);
    }

    public function isStreamTty($stream = null): bool
    {
        $stream = Dev::apply('_in', $stream);
        Dev::do('_before', [$stream, $this]);

        if ($stream === null) {
            $stream = STDOUT;
        }

        if (function_exists('stream_isatty')) {
            $result = @stream_isatty($stream);
        } elseif (function_exists('posix_isatty')) {
            $result = @posix_isatty($stream);
        } else {
            $result = false;
        }

        $result = (bool)Dev::apply('_out', $result);
        if ($this instanceof \BlueFission\Obj) {
            $this->trigger(Event::PROCESSED, new Meta(data: $result));
        }
        Dev::do('_after', [$result, $this]);

        return $result;
    }
}
