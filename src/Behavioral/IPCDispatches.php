<?php

namespace BlueFission\Behavioral;

use BlueFission\IPC\IIPC;

/**
 * Trait IPCDispatches
 *
 * Provides inter-process communication (IPC) dispatching and listening capabilities
 * via a shared IPC object. Classes using this trait can send messages to and listen
 * for messages from specific channels.
 */
trait IPCDispatches
{
    protected ?IIPC $_ipc = null;

    /**
     * Injects an IPC instance into the class.
     *
     * @param IIPC $ipc The IPC object to use for communication
     * @return static
     */
    public function setIPC(IIPC $ipc): static
    {
        $this->_ipc = $ipc;

        return $this;
    }

    /**
     * Sends a message to a specific IPC channel.
     *
     * @param string $channel The channel to write to
     * @param mixed $message The message payload to send
     * @return static
     */
    public function dispatchIPC(string $channel, mixed $message): static
    {
        if ($this->_ipc) {
            $this->_ipc->write($channel, $message);
        }

        return $this;
    }

    /**
     * Listens to a specific IPC channel and processes each message with a callback.
     *
     * @param string $channel The channel to listen to
     * @param callable $callback The function to run on each received message
     * @return static
     */
    public function listenIPC(string $channel, callable $callback): static
    {
        if ($this->_ipc) {
            $messages = $this->_ipc->read($channel);
            foreach ($messages as $message) {
                $callback($message);
            }
            $this->_ipc->clear($channel);
        }

        return $this;
    }
}
