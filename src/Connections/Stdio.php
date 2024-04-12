<?php

namespace BlueFission\Connections;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

/**
 * Class StdioConnection
 * 
 * This class is designed to handle standard input/output operations
 * extending the Connection class functionality to stdio.
 */
class Stdio extends Connection
{
    /**
     * Opens the standard input or output as a stream.
     * 
     * @param string $mode 'input' for stdin, 'output' for stdout
     * @return $this
     */
    public function open($mode = 'input'): IObj
    {
        if ($mode == 'input') {
            $this->_connection = STDIN;
        } else {
            $this->_connection = STDOUT;
        }

        $this->status(self::STATUS_CONNECTED);
        $this->perform(Event::LOAD); // Signal that the stream has been loaded
        return $this;
    }

    /**
     * Reads data from standard input.
     * 
     * @return $this
     */
    public function read()
    {
        if ($this->_connection === STDIN) {
            stream_set_blocking($this->_connection, false);
            $data = fgets($this->_connection);

            if ($data !== false) {
                $this->_result = $data;
                $this->perform(Event::SUCCESS, ['data' => $data]); // Emit success with data
            } else {
                $this->perform(Event::FAILED, ['error' => 'No data']); // Emit failure
            }
        }

        return $this;
    }

    /**
     * Writes data to standard output.
     * 
     * @param string $data Data to write
     * @return $this
     */
    public function write($data)
    {
        if ($this->_connection === STDOUT) {
            $written = fwrite($this->_connection, $data);

            if ($written !== false) {
                $this->perform(Event::SUCCESS, ['data' => $data]); // Emit success with data
            } else {
                $this->perform(Event::FAILED, ['error' => 'Failed to write data']); // Emit failure
            }
        }

        return $this;
    }

    /**
     * Close the connection (STDIN or STDOUT)
     * 
     * @return $this
     */
    public function close(): IObj
    {
        if (is_resource($this->_connection)) {
            fclose($this->_connection);
        }
        $this->_connection = null;
        $this->status(self::STATUS_DISCONNECTED);
        $this->perform(Event::UNLOAD); // Signal that the stream has been unloaded

        return $this;
    }

    /**
     * Runs a query, not applicable for Stdio, but we implement to fulfill the abstract requirements.
     * 
     * @param string|null $query Optional data for write
     */
    public function query($query = null)
    {
        if ($query) {
            $this->write($query);
        } else {
            $this->read();
        }
    }
}
