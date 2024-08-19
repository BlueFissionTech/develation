<?php
namespace BlueFission\Behavioral;

use BlueFission\IPC\IPC;

trait IPCDispatches {
    protected $ipc;

    public function setIPC(IPC $ipc) {
        $this->ipc = $ipc;
    }

    public function dispatchIPC($channel, $message) {
        if ($this->ipc) {
            $this->ipc->write($channel, $message);
        }
    }

    public function listenIPC($channel, callable $callback) {
        if ($this->ipc) {
            $messages = $this->ipc->read($channel);
            foreach ($messages as $message) {
                $callback($message);
            }
            $this->ipc->clear($channel);
        }
    }
}