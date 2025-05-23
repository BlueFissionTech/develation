<?php
namespace BlueFission\Behavioral;

use BlueFission\IPC\IPC;

/**
 * Trait IPCDispatches
 *
 * Provides inter-process communication (IPC) dispatching and listening capabilities
 * via a shared IPC object. Classes using this trait can send messages to and listen
 * for messages from specific channels.
 */
trait IPCDispatches {
    protected $_ipc;

/**
 * Injects an IPC instance into the class.
 *
 * @param IPC $ipc The IPC object to use for communication
 * @return void
 */
public function setIPC(IPC $ipc): void {
    $this->_ipc = $ipc;
    }

/**
 * Sends a message to a specific IPC channel.
 *
 * @param string $channel The channel to write to
 * @param mixed $message The message payload to send
 * @return void
 */
public function dispatchIPC(string $channel, $message): void {
    if ($this->_ipc) {
            $this->_ipc->write($channel, $message);
        }
    }

/**
 * Listens to a specific IPC channel and processes each message with a callback.
 *
 * @param string $channel The channel to listen to
 * @param callable $callback The function to run on each received message
 * @return void
 */
public function listenIPC(string $channel, callable $callback): void {
    if ($this->_ipc) {
            $messages = $this->_ipc->read($channel);
            foreach ($messages as $message) {
                $callback($message);
            }
            $this->_ipc->clear($channel);
        }
    }
}