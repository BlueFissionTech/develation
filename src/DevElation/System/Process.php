<?php 

namespace BlueFission\System;

class Process {
	private $_handle;
	private $_command;
	private $_spec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "a"), // stderr is a file to write to
	);
	private $_pipes;
	private $_directory;

	public function __construct( $cmd, $dir = null )
	{
		if ($cmd)
		{
			$this->_command = $cmd;
		}
		if ($dir)
		{
			$this->_directory = $dir;
		}
	}

	public function send( $data )
	{
		fwrite($this->_pipes[0], $data);
	}

	public function response()
	{
		return stream_get_contents($this->_pipes[1]);
	}

	public function start()
	{
		//$command = 'nohup '.$this->_command.' > /dev/null 2>&1 & echo $!';
		$command = $this->_command;
		
		$this->_handle = proc_open( $command , $this->_spec , $this->_pipes, $this->_directory );
	}

	public function status()
	{
		$status = proc_get_status();
		if ( $status )
		{
			return $status['running'];
		}
		else
			return fread($this->_pipes[2]);
	}

	public function stop()
	{
		foreach ($this->_pipes as $pipe)
		{
			if ($pipe)
				fclose($pipe);
		}
		return proc_close( $this->_handle );
	}
}