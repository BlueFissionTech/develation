<?php
namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Behavioral\Behaviors\Event;

class Memory extends Storage {
    protected $stream;

    public function __construct($config = null) {
        parent::__construct($config);
    }

    public function activate(): IObj {
        $mode = $this->config('target') ?? 'memory';
        $this->stream = fopen('php://'.$mode, 'r+');
        if (!$this->stream) {
            throw new \RuntimeException("Unable to open php://$mode stream");
        }
        return $this;
    }

    public function deactivate(): IObj {
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;
        }
        return $this;
    }

    protected function _read(): void {
        rewind($this->stream);
        $contents = stream_get_contents($this->stream);
        $this->_contents = $contents ? json_decode($contents, true) : [];
    }

    protected function _write(): void {
        ftruncate($this->stream, 0);
        rewind($this->stream);
        fwrite($this->stream, json_encode($this->_contents));
    }

    protected function _delete(): void {
        ftruncate($this->stream, 0);
        rewind($this->stream);
    }
}