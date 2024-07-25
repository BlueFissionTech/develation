<?php
namespace BlueFission\Data\Storage;

use Memcached as MemcachedClient;

class Memcached extends Storage {
    protected $_client;
    protected $_key;

    public function __construct($config = null) {
        parent::__construct($config);
        $this->_client = new MemcachedClient();
        $this->_client->addServer($this->config('host'), $this->config('port'));
        $this->_key = $this->config('key');
    }

    public function activate(): IObj {
        $this->_source = $this->_client->get($this->_key) ?? [];
        return $this;
    }

    public function deactivate(): IObj {
        $this->_source = null;
        return $this;
    }

    protected function _read(): void {
        $this->_contents = $this->_source;
    }

    protected function _write(): void {
        $this->_client->set($this->_key, $this->_contents);
    }

    protected function _delete(): void {
        $this->_client->delete($this->_key);
    }
}