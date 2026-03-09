<?php

namespace BlueFission\System;

use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Process is a wrapper class for the PHP proc_open function
 * and is used to start, manage and stop a system process.
 */
class Process implements IDispatcher
{
    use Dispatches {
        Dispatches::__construct as private __dConstruct;
    }

    /**
     * The command to be executed
     *
     * @var string
     */
    protected $_command;

    /**
     * The working directory for the command to be executed in
     *
     * @var string
     */
    protected $_cwd;

    /**
     * The environment variables for the command to be executed with
     *
     * @var array
     */
    protected $_env;

    /**
     * The descriptorspec for the command to be executed with
     *
     * @var array
     */
    protected $_descriptorspec;

    /**
     * The options for the command to be executed with
     *
     * @var array
     */
    protected $_options;

    /**
     * An array of pipes for the command to be executed with
     *
     * @var array
     */
    protected $_pipes = [];

    /**
     * Private variable that holds the default pipe specifications for the process.
     *
     * @var array
     */
    private $_spec = [
        0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
        1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
        2 => ["pipe", "a"], // stderr is a file to write to
    ];

    /**
     * The process resource created by proc_open
     *
     * @var resource
     */
    protected $_process;

    /**
     * The status of the process
     *
     * @var array
     */
    protected $_status;

    /**
     * The output of the process
     *
     * @var string
     */
    protected $_output;

    /**
     * Whether to use file-based output capture for Windows.
     *
     * @var bool
     */
    protected $_windowsSafeMode = false;

    /**
     * Temporary stdout file used in Windows safe mode.
     *
     * @var string|null
     */
    protected $_stdoutFile = null;

    /**
     * Temporary stderr file used in Windows safe mode.
     *
     * @var string|null
     */
    protected $_stderrFile = null;

    /**
     * Constructs a new instance of the Process class with the given command, cwd, env, descriptorspec, and options
     *
     * @param string $command The command to be executed
     * @param string|null $cwd The working directory for the command to be executed in
     * @param array|null $env The environment variables for the command to be executed with
     * @param array $descriptorspec The descriptorspec for the command to be executed with
     * @param array $options The options for the command to be executed with
     */
    public function __construct($command, $cwd = null, $env = null, $descriptorspec = null, $options = [])
    {
        $this->__dConstruct();
        $this->_command = $command;
        $this->_cwd = $cwd ?? getcwd();
        $this->_env = $env;
        $this->_descriptorspec = $descriptorspec ?? $this->_spec;
        $this->_options = $options;
        $windowsSafe = $options['windows_safe'] ?? $options['windowsSafe'] ?? false;
        $this->_windowsSafeMode = $this->isWindows() && (bool)$windowsSafe;

        $this->trigger(Event::LOAD);
    }

    public function __get($name)
    {
        if ('process' == $name) {
            return $this->_process;
        }

        return null;
    }

    /**
     * Starts the process execution
     */
    public function start()
    {
        $descriptorSpec = $this->_descriptorspec;
        if ($this->_windowsSafeMode) {
            $this->prepareWindowsSafeCapture();
            $descriptorSpec = [
                0 => $this->_descriptorspec[0] ?? ["pipe", "r"],
                1 => ["file", $this->_stdoutFile, "a"],
                2 => ["file", $this->_stderrFile, "a"],
            ];
        }

        $this->_process = proc_open($this->_command, $descriptorSpec, $this->_pipes, $this->_cwd, $this->_env, $this->_options);
        $this->trigger(Action::CONNECT);

        if (is_resource($this->_process)) {
            $this->trigger(Event::STARTED);
            // Make the streams non-blocking
            if (isset($this->_pipes[1]) && is_resource($this->_pipes[1])) {
                stream_set_blocking($this->_pipes[1], false);
            }
            if (isset($this->_pipes[2]) && is_resource($this->_pipes[2])) {
                stream_set_blocking($this->_pipes[2], false);
            }
            $this->trigger(Event::CONNECTED);
        } else {
            $message = "Error starting process: " . $this->_command;
            error_log($message);
            $this->trigger(Event::ERROR, new Meta(when: Action::CONNECT, info: $message));
        }

        return $this;
    }

    public function pipes($index = 1)
    {
        return $this->_pipes[$index];
    }

    /**
     * Gets the output of the process
     *
     * @return string The output of the process
     */
    public function output()
    {
        $this->trigger(Action::READ);
        $this->trigger(State::READING);
        if ($this->_windowsSafeMode && $this->_stdoutFile && file_exists($this->_stdoutFile)) {
            $this->_output = (string)file_get_contents($this->_stdoutFile);
        } else {
            $this->_output = isset($this->_pipes[1]) ? stream_get_contents($this->_pipes[1]) : '';
        }
        $this->trigger(Event::READ);

        return $this->_output;
    }

    /**
     * Method used to retrieve the status of the process.
     *
     * @return bool|string If the process is running, returns true. Otherwise returns the error message.
     */
    public function status()
    {
        $this->_status = proc_get_status($this->_process);
        if ($this->_status) {
            return $this->_status['running'];
        } else {
            if ($this->_windowsSafeMode && $this->_stderrFile && file_exists($this->_stderrFile)) {
                return (string)file_get_contents($this->_stderrFile);
            }
            return fread($this->_pipes[2], 2096);
        }
    }

    /**
     * Stop the running process
     * @return int Returns the termination status of the process that was run.
     */
    public function stop()
    {
        $this->trigger(Action::STOP);

        foreach ($this->_pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
                $this->trigger(Event::DISCONNECTED);
            }
        }
        proc_close($this->_process);
        $this->cleanupWindowsSafeCapture();
        $this->trigger(Event::STOPPED);
        // return $status;

        return $this;
    }

    /**
     * Close the process resource
     * @return int Returns the exit code of the process that was run.
     */
    public function close()
    {
        $this->trigger(Action::DISCONNECT);
        $status = proc_close($this->_process);
        $this->cleanupWindowsSafeCapture();
        $this->trigger(Event::DISCONNECTED);

        return $status;
    }

    /**
     * Check if the runtime OS is Windows.
     *
     * @return bool
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Initialize temporary files for safe output capture on Windows.
     *
     * @return void
     */
    protected function prepareWindowsSafeCapture(): void
    {
        $this->_stdoutFile = tempnam(sys_get_temp_dir(), 'bf_proc_out_');
        $this->_stderrFile = tempnam(sys_get_temp_dir(), 'bf_proc_err_');
    }

    /**
     * Remove temporary files created for Windows safe capture.
     *
     * @return void
     */
    protected function cleanupWindowsSafeCapture(): void
    {
        if ($this->_stdoutFile && file_exists($this->_stdoutFile)) {
            @unlink($this->_stdoutFile);
        }
        if ($this->_stderrFile && file_exists($this->_stderrFile)) {
            @unlink($this->_stderrFile);
        }

        $this->_stdoutFile = null;
        $this->_stderrFile = null;
    }
}
