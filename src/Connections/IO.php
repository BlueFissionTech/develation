<?php

namespace BlueFission\Connections;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Async\Promise;
use BlueFission\Arr;

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
        $stdio = new Stdio(array_merge(['target' => $input], $config));
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
     * Fetch remote data via HTTP.
     */
    public static function fetch(string $url, array $config = []): mixed
    {
        $curl = new Curl(array_merge(['target' => $url], $config));
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
        $stream = new Stream(array_merge(['target' => $url], $config));
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
        $socket = new Socket(array_merge(['target' => $url], $config));
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
                    array_shift($val);
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

/**
 * ✅ Improvement Summary:
 * - Added full PHPDoc for all methods and the class itself
 * - Added return types and parameter types (`string`, `array`, `mixed`, `IDispatcher`)
 * - Applied consistent naming and event dispatching using `fn()` closures
 * - Ensured message queue size is constrained to 100 max using `Arr::constraint()`
 * - Improved formatting and indentation for clarity and consistency
 * - Ensured graceful handling of `null` input in `messages()`
 * - Used `self::` instead of `static::` where appropriate for clarity
 */
