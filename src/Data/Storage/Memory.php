<?php

namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\DevElation as Dev;
use BlueFission\Ref;

class Memory extends Storage
{
    protected ?Ref $_stream = null;

    public function __construct($config = null)
    {
        parent::__construct($config);
    }

    public function activate(): IObj
    {
        $mode = $this->config('target') ?? 'memory';
        $handle = fopen('php://'.$mode, 'r+');
        if (!$handle) {
            throw new \RuntimeException("Unable to open php://$mode stream");
        }
        $this->_stream = Ref::resource($handle, ['owned' => true, 'target' => 'php://'.$mode]);

        return parent::activate();
    }

    private function _disconnect()
    {
        if ($this->_stream) {
            $this->_stream->close();
        }
    }

    protected function _read(): void
    {
        $handle = $this->_stream?->unwrap();
        if (!is_resource($handle)) {
            $this->_contents = [];
            return;
        }

        rewind($handle);
        $contents = $this->_stream->read();

        $contents = Dev::apply(null, $contents);

        $this->_contents = $contents ? json_decode($contents, true) : [];
    }

    protected function _write(): void
    {
        $handle = $this->_stream?->unwrap();
        if (!is_resource($handle)) {
            return;
        }

        ftruncate($handle, 0);
        rewind($handle);

        $contents = Dev::apply(null, $this->_contents);
        
        $this->_stream->write(json_encode($contents));
    }

    protected function _delete(): void
    {
        $handle = $this->_stream?->unwrap();
        if (!is_resource($handle)) {
            return;
        }

        ftruncate($handle, 0);
        rewind($handle);
    }
}
