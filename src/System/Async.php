<?php

class Async extends Programmable {

	/**
	 * Configuration array
	 *
	 * @var array
	 */
	private $_config = array(
		'location'=>'.',
		'interpreter'=>'/usr/bin/php',
		'memory'=>pow(1024,2),
	);

	/**
	 * Post data to a URL
	 *
	 * @param string $url The URL to post to
	 * @param array $params An array of data to post
	 *
	 * @return void
	 */
	public function post($url, array $params)
	{
		$url = $this->config('location');
		$params = $this->_data;

	    foreach ($params as $key => &$val) {
	      if (is_array($val)) $val = implode(',', $val);
	        $post_params[] = $key.'='.urlencode($val);  
	    }
	    $post_string = implode('&', $post_params);

	    $parts=parse_url($url);

	    $fp = fsockopen($parts['host'],
	        isset($parts['port'])?$parts['port']:80,
	        $errno, $errstr, 30);

	    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
	    $out.= "Host: ".$parts['host']."\r\n";
	    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
	    $out.= "Content-Length: ".strlen($post_string)."\r\n";
	    $out.= "Connection: Close\r\n\r\n";
	    if (isset($post_string)) $out.= $post_string;

	    fwrite($fp, $out);
	    fclose($fp);
	}

	/**
	 * Queue data for later processing
	 *
	 * @return void
	 */
	public function queue() {

	}

	/**
	 * Execute the command in a shell
	 *
	 * @return void
	 */
	public function shell() {
		set_time_limit(0);
		$interpreter = $this->config('interpreter');
		$file = $config('location');
		$cmd = 'nohup nice -n 10 '.$interpreter.' -f '.$location.' '.$args.' >> /path/to/log/file.log';
		$pid = shell_exec($cmd);
	}

	/**
	 * Spawn a child process
	 *
	 * @return void
	 */
	public function child() {
		if (! function_exists('pcntl_fork')) return;

		$pid = pcntl_fork();
		if ($pid == -1) {
		     return;
		} else if ($pid) {
		     // we are the parent
		     pcntl_wait($status); //Protect against Zombie children
		} else {
			if (function_exists('cli_set_process_title')) {
				cli_set_process_title($title);
			} elseif (function_exists('setproctitle')) {
				setproctitle( $title )
			}
			if (function_exists('setthreadtitle') {
		    	setthreadtitle($title);
			}
		}
	}

	// Thanks to kenneth at fellowrock dot com
	// https://secure.php.net/manual/en/function.pcntl-fork.php#115855
	/**
	 * Executes multiple processes concurrently by forking the process.
	 *
	 * @param array $options The options for the forking process.
	 *   - process: An array of functions to be executed concurrently.
	 *   - size: The size of the shared memory block for each function.
	 *   - callback: A function to be executed after all the processes are finished.
	 * @return void
	 */
	public function fork($options) 
	{
		if (! function_exists('pcntl_fork')) return;
	    $shared_memory_monitor = shmop_open(ftok(__FILE__, chr(0)), "c", 0644, count($options['process']));
	    $shared_memory_ids = (object) array();
	    for ($i = 1; $i <= count($options['process']); $i++) 
	    {
	        $shared_memory_ids->$i = shmop_open(ftok(__FILE__, chr($i)), "c", 0644, $options['size']);
	    }
	    for ($i = 1; $i <= count($options['process']); $i++) 
	    { 
	        $pid = pcntl_fork(); 
	        if (!$pid) 
	        { 
	            if($i == 1) {
	                usleep(100000);
	            }
	            $shared_memory_data = $options['process'][$i - 1]();
	            shmop_write($shared_memory_ids->$i, $shared_memory_data, 0);
	            shmop_write($shared_memory_monitor, "1", $i-1);
	            exit($i); 
	        } 
	    } 
	    while (pcntl_waitpid(0, $status) != -1) 
	    { 
	        if(shmop_read($shared_memory_monitor, 0, count($options['process'])) == str_repeat("1", count($options['process'])))
	        {
	            $result = array();
	            foreach($shared_memory_ids as $key=>$value)
	            {
	                $result[$key-1] = shmop_read($shared_memory_ids->$key, 0, $options['size']);
	                shmop_delete($shared_memory_ids->$key);
	            }
	            shmop_delete($shared_memory_monitor);
	            $options['callback']($result);
	        }    
	    } 
	}

	/* use for above

	// Create shared memory block of size 1M for each function.
	$options['size'] = pow(1024,2); 

	// Define 2 functions to run as its own process.
	$options['process'][0] = function()
	{
	    // Whatever you need goes here...
	    // If you need the results, return its value.
	    // Eg: Long running proccess 1
	    sleep(1);
	    return 'Hello ';
	};
	$options['process'][1] = function()
	{
	    // Whatever you need goes here...
	    // If you need the results, return its value.
	    // Eg:
	    // Eg: Long running proccess 2
	    sleep(1);
	    return 'World!';
	};
	$options['callback'] = function($result)
	{
	    // $results is an array of return values...
	    // $result[0] for $options['process'][0] &
	    // $result[1] for $options['process'][1] &
	    // Eg:
	    echo $result[0].$result[1]."\n";    
	};
	
	*/
}