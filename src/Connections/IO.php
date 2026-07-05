<?php

namespace BlueFission\Connections;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Async\Promise;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Data\File;
use BlueFission\Data\FileSystem;

/**
 * IO utility class for performing common input/output operations
 * across Stdio, Curl, Stream, and Socket sources.
 */
class IO
{
    protected static array $_filters = [];
    protected static array $_defaults = [];
    protected static mixed $_messages = null;
    protected static mixed $_listener = null;

    /**
     * Handle stdio input/output.
     */
    public static function std($input = null, array $config = []): mixed
    {
        // Fast-path: when a concrete input file is provided, avoid the
        // behavioral/stdio stack and just copy/read the file directly.
        $source = Str::is($input) ? Str::make($input) : null;
        $file = new File();

        if ($source && $source->isNotEmpty() && $file->exists($source())) {
            $data = FileSystem::fileContents($source());
            $options = Arr::make($config);

            if ($options->hasKey('output') && Str::is($options['output'])) {
                @file_put_contents($options['output'], $data);
            }

            return self::applyFilters($data);
        }

        $stdio = new Stdio(Arr::make(['target' => $input])->merge($config)->toArray());
        $stdio
            ->when(new Event(Event::CONNECTED), fn ($b) => self::messages("Connected to stdio", $b))
            ->when(new Event(Event::COMPLETE), fn ($b) => self::messages("Communication complete", $b))
            ->when(new Event(Event::FAILURE), fn ($b) => self::messages("Communication failed", $b))
            ->when(new Event(Event::ERROR), fn ($b) => self::messages("Communication error", $b))
            ->open();

        $result = $stdio->query()->result();
        $stdio->close();

        return self::applyFilters($result);
    }

    /**
     * Read request/body input without using non-interactive stream polling.
     */
    public static function input(mixed $source = null): string
    {
        return (string)self::applyFilters(Stdio::input($source));
    }

    /**
     * Fetch remote data via HTTP.
     */
    public static function fetch(string $url, array $config = []): mixed
    {
        $curl = new Curl(Arr::make(['target' => $url, 'timeout' => 5, 'connect_timeout' => 3])->merge($config)->toArray());
        $curl
            ->when(new Event(Event::CONNECTED), fn ($b) => self::messages("Connected to remote", $b))
            ->when(new Event(Event::COMPLETE), fn ($b) => self::messages("Read complete", $b))
            ->when(new Event(Event::FAILURE), fn ($b) => self::messages("Read failed", $b))
            ->when(new Event(Event::ERROR), fn ($b) => self::messages("Read error", $b))
            ->open();

        $result = $curl->query()->result();
        $curl->close();

        return self::applyFilters($result);
    }

    /**
     * Stream data from a source.
     */
    public static function stream(string $url, array $config = []): mixed
    {
        $stream = new Stream(Arr::make(['target' => $url, 'timeout' => 5])->merge($config)->toArray());
        $stream
            ->when(new Event(Event::CONNECTED), fn ($b) => self::messages("Connected to stream", $b))
            ->when(new Event(Event::COMPLETE), fn ($b) => self::messages("Read complete", $b))
            ->when(new Event(Event::FAILURE), fn ($b) => self::messages("Read failed", $b))
            ->when(new Event(Event::ERROR), fn ($b) => self::messages("Read error", $b))
            ->open();

        $result = $stream->query()->result();
        $stream->close();

        return self::applyFilters($result);
    }

    /**
     * Communicate over a socket.
     */
    public static function sock(string $url, array $config = []): mixed
    {
        $socket = new Socket(Arr::make(['target' => $url, 'timeout' => 5])->merge($config)->toArray());
        $socket
            ->when(new Event(Event::CONNECTED), fn ($b) => self::messages("Connected to socket", $b))
            ->when(new Event(Event::COMPLETE), fn ($b) => self::messages("Read complete", $b))
            ->when(new Event(Event::FAILURE), fn ($b) => self::messages("Read failed", $b))
            ->when(new Event(Event::ERROR), fn ($b) => self::messages("Read error", $b))
            ->open();

        $result = $socket->query()->result();
        $socket->close();

        return self::applyFilters($result);
    }

    /**
     * Set default configuration values.
     */
    public static function setDefault(string $key, mixed $value): void
    {
        self::$_defaults[$key] = $value;
    }

    /**
     * Register a callable filter to modify I/O results.
     */
    public static function addFilter(callable $filter): void
    {
        self::$_filters[] = $filter;
    }

    /**
     * Apply registered filters to data.
     */
    protected static function applyFilters(mixed $data): mixed
    {
        foreach (self::$_filters as $filter) {
            $data = call_user_func($filter, $data);
        }
        return $data;
    }

    /**
     * Log a message or retrieve logged messages.
     */
    public static function messages(string|null $input = null, mixed $event = null): mixed
    {
        if (self::$_messages === null) {
            self::$_messages = (new Arr())->constraint(function (&$val) {
                if (Arr::size($val) > 100) {
                    $val = Arr::make($val)->slice(1)->toArray();
                }
            });
        }

        if ($input === null) {
            return self::$_messages->toArray();
        }

        self::$_messages[] = $input;

        if (self::$_listener instanceof IDispatcher) {
            self::$_listener->trigger($event ?? Event::MESSAGE, new Meta(info: $input));
        }

        return null;
    }

    /**
     * Set a global event listener.
     */
    public static function listener(IDispatcher $listener): void
    {
        self::$_listener = $listener;
    }
}
