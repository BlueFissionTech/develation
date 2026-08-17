<?php

namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Val;
use BlueFission\Connections\Redis as RedisConnection;
use InvalidArgumentException;

class Redis extends Storage
{
    protected $_config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2.5,
        'username' => null,
        'password' => null,
        'database' => 0,
        'prefix' => '',
        'key' => null,
        'ttl' => 0,
    ];

    private RedisConnection $_redis;
    private bool $_ownsConnection;

    public function __construct($config = null, ?RedisConnection $connection = null)
    {
        parent::__construct($config);

        $this->_ownsConnection = Val::isNull($connection);
        $this->_redis = $connection ?? new RedisConnection($this->_config);
    }

    public function activate(): IObj
    {
        $this->assertKey();
        $this->_redis->open();
        $this->_source = $this->_redis->connection();

        return parent::activate();
    }

    public function deactivate(): IObj
    {
        if ($this->_ownsConnection) {
            $this->_redis->close();
        }

        return parent::deactivate();
    }

    protected function _read(): void
    {
        $value = $this->_redis->command('get', [$this->storageKey()]);
        $this->_contents = $value === false ? null : unserialize($value, ['allowed_classes' => true]);
    }

    protected function _write(): void
    {
        $value = serialize($this->_contents);
        $ttl = (int)$this->config('ttl');

        if ($ttl > 0) {
            $this->_redis->command('setEx', [$this->storageKey(), $ttl, $value]);
            return;
        }

        $this->_redis->command('set', [$this->storageKey(), $value]);
    }

    protected function _delete(): void
    {
        $this->_redis->command('del', [$this->storageKey()]);
        $this->_contents = null;
    }

    private function storageKey(): string
    {
        return $this->_redis->key((string)$this->config('key'));
    }

    private function assertKey(): void
    {
        if (Val::isNull($this->config('key')) || $this->config('key') === '') {
            throw new InvalidArgumentException('Redis storage requires a non-empty key.');
        }
    }
}
