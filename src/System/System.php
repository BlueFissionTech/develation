<?php 

namespace BlueFission\System;

use BlueFission\Val;
use BlueFission\System\Machine;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;

/**
 * Class System
 * This class is used to run system commands.
 */
class System implements IDispatcher {
	use Dispatches;
	
	/**
	 * @var string $response The output of the command
	 */
	protected $response;
	/**
	 * @var string $status
	 */
	protected $status;
	/**
	 * @var Process $process The process information of the command
	 */
	protected $processes = [];
	/**
	 * @var int $timeout The maximum execution time of the command in seconds
	 */
	protected $timeout = 60;
	/**
	 * @var string $cwd The current working directory of the command
	 */
	protected $cwd;
	/**
	 * @var string $outputFile The file to write the command output to
	 */
	protected $outputFile;

	protected $command;

	protected $process;

	protected $readStreams;

	/**
	 * Check if the command is valid before running it
	 *
	 * @param string $command
	 * @return boolean
	 * 
	 */
	public function isValidCommand($command) {
		// check is the command is a system registered command
		$valid = true;

		if (empty($command)) {
			return false;
		}

		// Get first word of command
		$command = explode(' ', $command)[0];

		$command = escapeshellcmd($command);
		if ( (new Machine())->getOS() == 'Windows' ) {
			$valid = shell_exec("help $command");
			// Parse the output to see if we got a help output or an error ("This command is not supported by the help utility")
			$valid = strpos($valid, 'is not supported') === false;
		} else {
			$valid = shell_exec("which $command");
			// Parse the output to see if we got a help output or an error
			$valid = strpos($valid, 'not found') === false;
		}


		return $valid;
	}

	/**
	* Execute a command
	*
	* @param string $command  The command to execute
	* @param array $options Additional options for the command
	*
	* @throws \InvalidArgumentException when $command is empty or not a string
	*/
	public function run( $command, $options = [] ) {
		$this->trigger(Action::RUN);
		if (!$command) {
			$message = "Command cannot be empty!";
			$this->trigger(Event::EXCEPTION, new Meta( when: Action::RUN, info: $message));
			throw( new \InvalidArgumentException($message) );
		}

		if (!Val::isEmpty($options)) {
			foreach ($options as $opt) {
				$command .= ' ' . escapeshellarg($opt);
			}
		}

		$this->command = $command;

		$descriptorspec = [
			0 => ["pipe", "r"],
			1 => ["pipe", "w"],
			2 => ["pipe", "w"]
		];

		if (Val::is($this->outputFile)) {
			$descriptorspec[1] = ["file", $this->outputFile, "a"];
		}

		$options = [
			'timeout' => $this->timeout,
			'cwd' => $this->cwd
		];

		$process = new Process($command, $this->cwd, null, $descriptorspec, $options);
        $this->echo($process, [Event::STARTED, Event::STOPPED, Event::ERROR]);

        // Listen to process completion to handle cleanup or additional tasks
        $process->when(Event::COMPLETE, function($event, $args) {
            $this->status = "Process completed with output: " . $args['output'];
        });

		$this->processes[] = $process;
		end($this->processes)->start();

		$this->response = end($this->processes)->output();

		return $process;
	}

	public function start( $command, $options = []) {
		$this->tigger(Action::START);

        if (!$command) {
        	$message = "Command cannot be empty!";
        	$this->trigger(Event::EXCEPTION, new Meta(when: Action::START, info: $message));
            throw( new \InvalidArgumentException($message) );
        }

        if (!Val::isEmpty($options)) {
            foreach ($options as $opt) {
                $command .= ' ' . escapeshellarg($opt);
            }
        }

        $this->command = $command;

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        if (isset($this->outputFile)) {
            $descriptorspec[1] = ["file", $this->outputFile, "a"];
        }

        $options = [
            'timeout' => $this->timeout,
            'cwd' => $this->cwd
        ];

        $process = new Process($command, $this->cwd, null, $descriptorspec, $options);

        // Echo process events
        $this->echo($process, [Event::STARTED, Event::STOPPED, Event::ERROR]);

        // Setup listening to completion
        $process->when(Event::COMPLETE, function($event, $args) {
            $this->status = "Process completed with output: " . $args['output'];
        });

        $processId = uniqid('process_');
    	$this->processes[$processId] = $process;
        end($this->processes)->start();

        // Make the streams non-blocking
        // stream_set_blocking(end($this->processes)->pipes(1), false);
        // stream_set_blocking(end($this->processes)->pipes(2), false);

        // Add the streams to readStreams
        $this->readStreams[] = end($this->processes)->pipes(1);
        $this->readStreams[] = end($this->processes)->pipes(2);

        $this->response = '';

        return $processId;
    }

    public function stop($processId) {
    	if (isset($this->processes[$processId])) {
    		$this->processes[$processId]->stop();
    		$this->trigger(Event::STOPPED);
    	}
    }

    public function readAvailableOutput($processId) {
	    if (isset($this->processes[$processId])) {
	        $read_streams = [$this->processes[$processId]->pipes(1), $this->processes[$processId]->pipes(2)];
	        $write_streams = null;
	        $except_streams = null;
	        $output = '';

	        while (stream_select($read_streams, $write_streams, $except_streams, 0, 200000) > 0) {
	            foreach ($read_streams as $stream) {
	                $output .= stream_get_contents($stream);
	            }
	        }

	        return $output;
	    }
	    return false;
	}


	public function writeInput($processId, $input) {
	    if (isset($this->processes[$processId])) {
	        $process = $this->processes[$processId];//['process'];
	        fwrite($process->pipes(0), $input);
	    }
	}

	public function readOutput($processId) {
	    if (isset($this->processes[$processId])) {
	        $process = $this->processes[$processId];//['process'];
	        return stream_get_contents($process->pipes(1));
	    }
	    return false;
	}

	public function isOutputAvailable(int $processId): bool
	{
	    $process = $this->processes[$processId] ?? null;

	    if (!$process) {
	        return false;
	    }

	    $status = stream_get_meta_data($process->pipes(1));
	    return !$status['eof'];
	}

	/**
	 * Get the process information of the command
	 *
	 * @return Process
	 */
	public function process() {
		return array_pop( $this->processes );
	}

	/**
	* Get the command that was run
	*
	* @return string
	*/
	public function getCommand()
	{
		return $this->command;
	}

	/**
	* Set or get the working directory
	*
	* @param string $cwd
	*/
	public function cwd($cwd = null)
	{
		if ( $cwd ) {
	    	$this->cwd = $cwd;
		}

		return $this->cwd;
	}

	/**
	* Get or set the timeout
	*
	* @return int
	*/
	public function timeout($timeout)
	{
		if ( $timeout ) {
			$this->timeout = $timeout;
		}

		return $this->timeout;
	}

	/**
	* Set or get the output file
	*
	* @param string $output_file
	*/
	public function outputFile($output_file)
	{
		if ( $output_file ) {
			$this->outputFile = $output_file;
		}
		return $this->outputFile;
	}

	/**
	* Get the response of the command
	*
	* @return mixed string|boolean response of the command or false if command was run in background
	*/
	public function response() {
		if ($this->response) {
			return $this->response;
		}
		return false;
	}

	/**
	* Get the message
	*
	* @return string message
	*/
	public function status() {
		return $this->status;
	}
}