<?php

namespace BlueFission\Connections;

use BadMethodCallException;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Val;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\State;
use RuntimeException;
use Throwable;

class Redis extends Connection
{
    protected $_config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2.5,
        'read_timeout' => 2.5,
        'retry_interval' => 0,
        'username' => null,
        'password' => null,
        'database' => 0,
        'prefix' => '',
        'persistent' => false,
        'persistent_id' => null,
    ];

    private ?object $_providedClient;

    public function __construct($config = null, ?object $client = null)
    {
        $this->_providedClient = $client;
        parent::__construct($config);
    }

    protected function _open(): void
    {
        if (Val::isNotNull($this->_connection)) {
            $this->connected();
            return;
        }

        try {
            $client = $this->_providedClient ?? $this->createClient();
            $method = $this->config('persistent') ? 'pconnect' : 'connect';
            $arguments = [
                (string)$this->config('host'),
                (int)$this->config('port'),
                (float)$this->config('timeout'),
                $method === 'pconnect' ? $this->config('persistent_id') : null,
                (int)$this->config('retry_interval'),
                (float)$this->config('read_timeout'),
            ];

            if (!$client->{$method}(...$arguments)) {
                throw new RuntimeException('Redis connection failed.');
            }

            $this->authenticate($client);

            if (!$client->select((int)$this->config('database'))) {
                throw new RuntimeException('Redis database selection failed.');
            }

            $this->_connection = $client;
            $this->connected();
        } catch (Throwable $exception) {
            $this->_connection = null;
            $this->status($exception->getMessage());
            $this->perform(
                [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(when: Action::CONNECT, info: $exception->getMessage())
            );
        }
    }

    protected function _close(): void
    {
        if (Val::isNotNull($this->_connection) && is_callable([$this->_connection, 'close'])) {
            $this->_connection->close();
        }

        $this->perform(State::DISCONNECTED);
    }

    public function query($query = null): IObj
    {
        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

        try {
            if (!Arr::is($query) || !isset($query[0])) {
                throw new BadMethodCallException('Redis queries require [command, arguments].');
            }

            $arguments = isset($query[1]) && Arr::is($query[1]) ? $query[1] : [];
            $this->_result = $this->command((string)$query[0], $arguments);
            $this->perform(
                [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED],
                new Meta(when: Action::PROCESS, data: $this->_result)
            );
        } catch (Throwable $exception) {
            $this->_result = false;
            $this->status($exception->getMessage());
            $this->perform(
                [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(when: Action::PROCESS, info: $exception->getMessage())
            );
        }

        return $this;
    }

    public function command(string $command, array $arguments = []): mixed
    {
        if (Val::isNull($this->_connection)) {
            $this->open();
        }

        if (Val::isNull($this->_connection)) {
            throw new RuntimeException($this->status() ?: self::STATUS_NOTCONNECTED);
        }

        if (!is_callable([$this->_connection, $command])) {
            throw new BadMethodCallException("Unsupported Redis command: {$command}");
        }

        return $this->_connection->{$command}(...$arguments);
    }

    public function key(string $key): string
    {
        return (string)$this->config('prefix') . $key;
    }

    private function createClient(): object
    {
        if (!class_exists('\Redis')) {
            throw new RuntimeException('Redis support requires ext-redis.');
        }

        return new \Redis();
    }

    private function authenticate(object $client): void
    {
        $password = $this->config('password');
        if (Val::isNull($password) || $password === '') {
            return;
        }

        $username = $this->config('username');
        $credentials = Val::isNotNull($username) && $username !== ''
            ? [(string)$username, (string)$password]
            : (string)$password;

        if (!$client->auth($credentials)) {
            throw new RuntimeException('Redis authentication failed.');
        }
    }

    private function connected(): void
    {
        $this->status(self::STATUS_CONNECTED);
        $this->perform(
            [Event::SUCCESS, Event::CONNECTED],
            new Meta(when: Action::CONNECT, info: self::STATUS_CONNECTED)
        );
    }
}
