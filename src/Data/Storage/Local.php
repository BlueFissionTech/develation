<?php
namespace BlueFission\Data\Storage;

class Local extends Storage {
    protected $data = [];

    public function activate(): IObj {
        $this->source = &$this->data;
        return $this;
    }

    protected function _read(): void {
        $this->contents = $this->source;
    }

    protected function _write(): void {
        $this->source = $this->contents;
    }

    protected function _delete(): void {
        $this->source = null;
    }
}
