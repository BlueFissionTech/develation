<?php

namespace BlueFission\IPC;

interface IIPC
{
    /**
     * Write a message to a channel.
     *
     * @param string $channel
     * @param mixed $message
     * @return void
     */
    public function write(string $channel, mixed $message): void;

    /**
     * Read messages from a channel.
     *
     * @param string $channel
     * @return array
     */
    public function read(string $channel): array;

    /**
     * Clear messages from a channel.
     *
     * @param string $channel
     * @return void
     */
    public function clear(string $channel): void;
}
