<?php
namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Behavioral\Behaviors\Event;

class Memory extends Storage {
    protected $_stream;

    public function __construct($config = null) {
        parent::__construct($config);
    }

    public function activate(): IObj {
        $mode = $this->config('target') ?? 'memory';
        $this->_stream = fopen('php://'.$mode, 'r+');
        if (!$this->_stream) {
            throw new \RuntimeException("Unable to open php://$mode stream");
        }

        return parent::activate();
    }

    private function _disconnect() {
        if ($this->_stream) {
            fclose($this->_stream);
        }
    }

    protected function _read(): void {
        rewind($this->_stream);
        $contents = stream_get_contents($this->_stream);
        $this->_contents = $contents ? json_decode($contents, true) : [];
    }

    protected function _write(): void {
        ftruncate($this->_stream, 0);
        rewind($this->_stream);
        fwrite($this->_stream, json_encode($this->_contents));
    }

    protected function _delete(): void {
        ftruncate($this->_stream, 0);
        rewind($this->_stream);
    }
}