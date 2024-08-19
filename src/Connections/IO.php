<?php

namespace BlueFission\Connections;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Async\Promise;

class IO {
    protected static $filters = [];
    protected static $defaults = [];
    protected static $messages = [];
    protected static $listener = null;

    public static function std($input = null, $config = []) {
        $stdio = new Stdio(array_merge(['target' => $input], $config));
        $stdio
        ->when( new Event( Event::CONNECTED ), function($b) { IO::messages("Connected to stdio", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::messages("Communication complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) { IO::messages("Communication failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) { IO::messages("Communication error", $b); })
        ->open();

        $result = $stdio->query()->result();
        $stdio->close();
        $data = static::applyFilters($result);

        return $data;
    }

    public static function fetch($url, $config = []) {
        $curl = new Curl(array_merge(['target' => $url], $config));
        $curl
        ->when( new Event( Event::CONNECTED ), function($b) { IO::messages("Connected to remote", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::messages("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) { IO::messages("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) { IO::messages("Read error", $b); })
        ->open();
        
        $result = $curl->query()->result();
        $curl->close();
        $data = static::applyFilters($result);

        return $data;
    }

    public static function stream($url, $config = []) {
        $stream = new Stream(array_merge(['target' => $url], $config));
        $stream
        ->when( new Event( Event::CONNECTED ), function($b) { IO::messages("Connected to stream", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::messages("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) { IO::messages("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) { IO::messages("Read error", $b); })
        ->open();
        
        $result = $stream->query()->result();
        $stream->close();
        $data = static::applyFilters($result);

        return $data;
    }

    public static function sock($url, $config = []) {
        $socket = new Socket(array_merge(['target' => $url], $config));
        $socket            
        ->when( new Event( Event::CONNECTED ), function($b) { IO::messages("Connected to socket", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::messages("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) { IO::messages("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) { IO::messages("Read error", $b); })
        ->open();
        
        $result = $socket->query()->result();
        $socket->close();
        $data = static::applyFilters($result);

        return $data;
    }

    public static function setDefault($key, $value) {
        self::$defaults[$key] = $value;
    }

    public static function addFilter(callable $filter) {
        self::$filters[] = $filter;
    }

    protected static function applyFilters($data) {
        foreach (self::$filters as $filter) {
            $data = call_user_func($filter, $data);
        }
        return $data;
    }

    public static function messages( $input = null, $event = null )
    {
        if ( static::$messages === null ) {
            static::$messages = (new Arr())->constraint(function(&$val) {
                if (Arr::size($val) > 100) {
                    array_shift($val);
                }
            });
        }

        if ( $input === null ) {
            return static::$messages->toArray();
        }

        static::$messages[] = $input;

        $listener = static::$listener;
        if ( $listener && $listener instanceof IDispatcher ) {
            $listener->trigger( $event ?? Event::MESSAGE, new Meta(info: $input));
        }
    }

    public static function listener( $listener )
    {
        static::$listener = $listener;
    }
}
