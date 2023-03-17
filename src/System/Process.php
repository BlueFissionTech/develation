<?php

namespace BlueFission\System;

/**
 * Class Process is a wrapper class for the PHP proc_open function 
 * and is used to start, manage and stop a system process.
 */
class Process {
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
    protected $pipes = [];

    /**
	 * Private variable that holds the default pipe specifications for the process.
	 *
	 * @var array
	 */
	private $spec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "a"), // stderr is a file to write to
	);

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
     * Constructs a new instance of the Process class with the given command, cwd, env, descriptorspec, and options
     *
     * @param string $command The command to be executed
     * @param string|null $cwd The working directory for the command to be executed in
     * @param array|null $env The environment variables for the command to be executed with
     * @param array $descriptorspec The descriptorspec for the command to be executed with
     * @param array $options The options for the command to be executed with
     */
    public function __construct($command, $cwd = null, $env = null, $descriptorspec = null, $options = []) {
        $this->_command = $command;
        $this->_cwd = $cwd ?? getcwd();
        $this->_env = $env;
        $this->_descriptorspec = $descriptorspec ?? $this->_spec;
        $this->_options = $options;
    }

    /**
     * Starts the process execution
     */
    public function start() {
        $this->_process = proc_open($this->_command, $this->_descriptorspec, $this->_pipes, $this->_cwd, $this->_env, $this->_options);
    }

    /**
     * Gets the output of the process
     *
     * @return string The output of the process
     */
    public function output() {
        $this->_output = stream_get_contents($this->_pipes[1]);
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
		if ( $status )
		{
			return $this->_status['running'];
		}
		else
			return fread($this->pipes[2]);
	}

    /**
     * Stop the running process
     * @return int Returns the termination status of the process that was run.
     */
    public function stop() {
        foreach ($this->_pipes as $pipe)
		{
			if ($pipe) {
				fclose($pipe);
			}
		}
		return proc_close( $this->_process );
    }

    /**
     * Close the process resource
     * @return int Returns the exit code of the process that was run.
     */
    public function close() {
        return proc_close($this->_process);
    }

}

