<?php
namespace BlueFission\Data\Storage;

class Local extends Storage {
    protected $_data = [];

    public function activate(): IObj {
        $this->_source = &$this->_data;
        return $this;
    }

    protected function _read(): void {
        $this->_contents = $this->_source;
    }

    protected function _write(): void {
        $this->_source = $this->_contents;
    }

    protected function _delete(): void {
        $this->_source = null;
    }
}
