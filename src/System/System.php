<?php 

namespace BlueFission\System;

/**
 * Class System
 * This class is used to run system commands.
 */
class System {
	
	/**
	 * @var string $_response The output of the command
	 */
	protected $_response;
	/**
	 * @var Process $_process The process information of the command
	 */
	protected $_processes = [];
	/**
	 * @var int $_timeout The maximum execution time of the command in seconds
	 */
	protected $_timeout = 60;
	/**
	 * @var string $_cwd The current working directory of the command
	 */
	protected $_cwd;
	/**
	 * @var string $_output_file The file to write the command output to
	 */
	protected $_output_file;

	/**
	 * Initialize the class
	 */
	public function __construct() {}

	/**
	 * Check if the command is valid before running it
	 *
	 * @param string $command
	 * @return boolean
	 */
	public function isValidCommand($command) {
	    $returnVal = exec($command . ' 2>&1', $output, $returnVal);
	    return !$returnVal;
	}

	/**
	* Execute a command
	*
	* @param string $command  The command to execute
	* @param boolean $background  Execute command in background
	* @param array $options Additional options for the command
	*
	* @throws \BadArgumentException when $command is empty or not a string
	*/
	public function run( $command, $background = false, $options = array() ) {
		if (!$command)
			throw( new \BadArgumentException("Command cannot be empty!") );

		if(!$this->isValidCommand($command))
			throw( new \BadArgumentException("Invalid command!") );

		if (!empty($options)) {
			foreach ($options as $opt) {
				$command .= ' ' . escapeshellarg($opt);
			}
		}

		$this->_command = $command;

		$descriptorspec = [
			0 => ["pipe", "r"],
			1 => ["pipe", "w"],
			2 => ["pipe", "w"]
		];

		if (isset($this->_output_file)) {
			$descriptorspec[1] = ["file", $this->_output_file, "a"];
		}

		$options = [
			'timeout' => $this->_timeout,
			'cwd' => $this->_cwd
		];

		$this->_process[] = new Process($command, $this->_cwd, null, $descriptorspec, $options);
		$this->_process->start();

		$this->_response = $this->_process->output();
	}

	/**
	 * Get the process information of the command
	 *
	 * @return Process
	 */
	public function process() {
		return array_pop( $this->_processes );
	}

	/**
	* Get the command that was run
	*
	* @return string
	*/
	public function getCommand()
	{
		return $this->_command;
	}

	/**
	* Set or get the working directory
	*
	* @param string $cwd
	*/
	public function cwd($cwd)
	{
		if ( $cwd ) {
	    	$this->_cwd = $cwd;
		}

		return $this->_cwd;
	}

	/**
	* Get or set the timeout
	*
	* @return int
	*/
	public function timeout($timeout)
	{
		if ( $timeout ) {
			$this->_timeout = $timeout;
		}

		return $this->_timeout;
	}

	/**
	* Set or get the output file
	*
	* @param string $output_file
	*/
	public function outputFile($output_file)
	{
		if ( $output_file ) {
			$this->_output_file = $output_file;
		}
		return $this->_output_file;
	}

	/**
	* Get the response of the command
	*
	* @return mixed string|boolean response of the command or false if command was run in background
	*/
	public function response() {
		if ($this->_success) {
			return $this->_response;
		}
		return false;
	}

	/**
	* Get the error message
	*
	* @return string Error message
	*/
	public function error() {
		return $this->_error;
	}

	/**
	* Check if the command was successfully executed
	*
	* @return boolean true if success, false otherwise
	*/
	public function success() {
		return $this->_success;
	}
}