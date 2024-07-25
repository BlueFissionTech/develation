<?php
namespace BlueFission\Behavioral;

use BlueFission\IPC\IPC;

trait IPCDispatches {
    protected $_ipc;

    public function setIPC(IPC $ipc) {
        $this->_ipc = $ipc;
    }

    public function dispatchIPC($channel, $message) {
        if ($this->_ipc) {
            $this->_ipc->write($channel, $message);
        }
    }

    public function listenIPC($channel, callable $callback) {
        if ($this->_ipc) {
            $messages = $this->_ipc->read($channel);
            foreach ($messages as $message) {
                $callback($message);
            }
            $this->_ipc->clear($channel);
        }
    }
}