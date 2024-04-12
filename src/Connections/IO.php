<?php

namespace BlueFission\Connections;

use BlueFission\Async\Promise;

class IO {
    protected static $_filters = [];
    protected static $_defaults = [];
    protected static $_messages = [];
    protected static $_listener = null;

    public static function in() {
        $stdio = new Stdio();
        $stdio
        ->when( new Event( Event::CONNECTED ), function($b) { IO::message("Connected to stdin", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::message("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) use( $listener ) { IO::message("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) use( $listener ) { IO::message("Read error", $b); })
        ->open('input');

        return new Promise(function($resolve, $reject) use ($stdio) {
            $data = $stdio->read();
            if ($data !== false) {
                $data = static::applyFilters($data);
                $resolve($data);
            } else {
                $reject('No data');
            }
            $stdio->close();
        });
    }

    public static function out($data) {
        $data = static::applyFilters($data);
        $stdio = new Stdio();
        $stdio
        ->when( new Event( Event::CONNECTED ), function($b) { IO::message("Connected to stdin", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::message("Write complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) use( $listener ) { IO::message("Write failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) use( $listener ) { IO::message("Write error", $b); })
        $stdio->open('output');
        $stdio->write($data);
        $stdio->close();
    }

    public static function fetch($url) {
        $curl = new Curl(['target' => $url]);
        $curl
        ->when( new Event( Event::CONNECTED ), function($b) { IO::message("Connected to remote", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::message("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) use( $listener ) { IO::message("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) use( $listener ) { IO::message("Read error", $b); })
        ->open();

        return new Promise(function($resolve, $reject) use ($curl) {
            $result = $curl->query();
            $curl->close();

            if ($result) {
                $data = static::applyFilters($result);
                $resolve($data);
            } else {
                $reject('Failed to fetch data');
            }
        });
    }

    public static function stream($url) {
        $stream = new Stream(['target' => $url]);
        $stream
        ->when( new Event( Event::CONNECTED ), function($b) { IO::message("Connected to stream", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::message("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) use( $listener ) { IO::message("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) use( $listener ) { IO::message("Read error", $b); })
        ->open();
        
        return new Promise(function($resolve, $reject) use ($stream) {
            $result = $stream->query();
            $stream->close();

            if ($result) {
                $data = static::applyFilters($result);
                $resolve($data);
            } else {
                $reject('Failed to read stream');
            }
        });
    }

    public static function sock($url) {
        $socket = new Socket(['target' => $url]);
        $socket            
        ->when( new Event( Event::CONNECTED ), function($b) { IO::message("Connected to socket", $b); })
        ->when( new Event( Event::COMPLETE ), function($b) { IO::message("Read complete", $b); })
        ->when( new Event( Event::FAILURE ), function($b) use( $listener ) { IO::message("Read failed", $b); })
        ->when( new Event( Event::ERROR ), function($b) use( $listener ) { IO::message("Read error", $b); })
        ->open();

        return new Promise(function($resolve, $reject) use ($socket) {
            $result = $socket->query();
            $socket->close();

            if ($result) {
                $data = static::applyFilters($result);
                $resolve($data);
            } else {
                $reject('Failed to open socket');
            }
        });
    }

    public static function setDefault($key, $value) {
        self::$__defaults[$key] = $value;
    }

    public static function addFilter(callable $filter) {
        self::$_filters[] = $filter;
    }

    protected static function applyFilters($data) {
        foreach (self::$_filters as $filter) {
            $data = call_user_func($filter, $data);
        }
        return $data;
    }

    public static function messages( $input = null, $event = null )
    {
        static::$_messages = $input;
        $listener = static::$_listener;
        if ( $listener ) {
            $listener->trigger( $event ?? Event::MESSAGE, $input);
        }
    }

    public static function listener( $listener )
    {
        static::$_listener = $listener
    }
}
