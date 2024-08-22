<?php
namespace BlueFission\Data\Storage;

use Memcached as MemcachedClient;

class Memcached extends Storage {
    protected $client;
    protected $key;

    public function __construct($config = null) {
        parent::__construct($config);
        $this->client = new MemcachedClient();
        $this->client->addServer($this->config('host'), $this->config('port'));
        $this->key = $this->config('key');
    }

    public function activate(): IObj {
        $this->source = $this->client->get($this->key) ?? [];
        return $this;
    }

    public function deactivate(): IObj {
        $this->source = null;
        return $this;
    }

    protected function _read(): void {
        $this->contents = $this->source;
    }

    protected function _write(): void {
        $this->client->set($this->key, $this->contents);
    }

    protected function _delete(): void {
        $this->client->delete($this->key);
    }
}