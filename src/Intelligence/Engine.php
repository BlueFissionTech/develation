<?php 
namespace BlueFission\Intelligence;

use BlueFission\Data\Queues\Queue as Queue;
use BlueFission\Intelligence\Collections\OrganizedCollection;

// Drives
/* 
	Security - stats
	Potential - scanning/testing resource gathering
	Desire - preference/personality
	Utility - service
	Expression - creation
	Insight - insight
	Consciousness
*/

// Efficiency = 1 - output / input ?
// Score = ( total transactions / correct transactions ) * ( total transactions / total successful transactions ) * avg transaction speed

// Conversational implicature = what is said versus what is implied (grice)
// Cooperate with most likely meaning from interpretation
// Speaker rules: quanity of information, quality of information, relation in information cohesion, manner of culture? (non-obscure, non-ambiguous, brief and orderly)
// Flout a maxim to draw attention to a point or intention
// Determine speaker vs audience meaning/attitude

// Platonic solid math for transaction size

// http://image-net.org/
// https://secure.php.net/manual/en/imagick.convolveimage.php

class Engine extends Intelligence {

	const TRANSACTION_MULTIPLIER = 10;
	const TRANSACTION_BASE_SIZE = 1;

	protected $_config = array(
		'template'=>'',
		'storage'=>'',
		'name'=>'brain',
	);

	private $_score = 0;
	private $_transaction_size;
	private $_level;

	private $_is_running = false;

	protected $_biases;
	protected $_scene;

	// protected $_inputs;
	protected $_strategies;
	protected $_memory;
	// protected $_outputs;

	protected $_starttime;
	protected $_stoptime;
	protected $_totaltime;

	public function __construct() {
		parent::__construct();
		
		$this->_services = new OrganizedCollection(); // Redefined to organize

		$this->_biases = new OrganizedCollection();
		$this->_scene = new Holoscene();
		$this->_strategies = new OrganizedCollection();
	}

	public function classify( $input ) {
		$result = $input;
		if ( $this->_scene->has($source) ) {
			$this->_scene->add($source);
		} else {
			foreach ( $this->_strategies as $strategy ) {
				$this->startclock();
				$strategy->process($input);
				$this->stopclock();

				$time = $this->time();
				$result = $strategy->guess();
				echo $strategy->score()."\n";
				if ($result) {
					break;
				}
			}
		}

		return $result;
	}

	public function getTransactionSize() {
		$this->_transaction_size = pow(self::TRANSACTION_BASE_SIZE * $this->_level, self::TRANSACTION_MULTIPLIER);
		return $this->_transaction_size;
	}

	public function react() {
		$this->perform();
	}

	public function input($name, $processor = null) {
		$sense = $name.'_sense';
		$input = $name.'_input';

		$this
			->delegate($input, '\BlueFission\Intelligence\Input', $processor )
			->delegate($sense, '\BlueFission\Intelligence\Sense', $this)
			
			->register($input, 'url', 'scan' )
			->register($sense, 'DoProcess', 'invoke')
			->register($this->name(), 'DoQueueInput', 'queueInput')
			->register($this->name(), 'DoTraining', 'addFrame')


			->route($input, $sense, 'OnComplete', 'DoProcess')
			->route($sense, $this->name(), 'OnSweep', 'DoTraining')
			->route($sense, $this->name(), 'OnCapture', 'DoQueueInput')
		;

		return $this;
	}

	public function addFrame( $frame ) {
		foreach ( $this->_strategies as $strategy ) {
			$this->service($strategy, 'train', $frame );
		}
	}

	public function strategy($name, $class) {
		$strategy = $name.'_strategy';
		$this->delegate($strategy, $class);

		$this->_strategies->add($strategy, $strategy);

		return $this;
	}

	public function queueInput( $behavior ) {
		$this->classify($behavior->_context);
		// Queue::enqueue( $behavior->_target->name(), $behavior->_context );
	}

	protected function init() {
		parent::init();
		// $this->behavior('OnExperience', array($this, 'queueInput'));
	}

	protected function startclock() {
		if ( function_exists('getrusage')) {
			$this->_starttime = getrusage();
		} else {
			$this->_starttime = microtime(true);
		}
	}

	protected function stopclock() {
		if ( function_exists('getrusage')) {
			$this->_stoptime = getrusage();
			$this->_totaltime = ($ru["ru_utime.tv_sec"]*1000 + intval($ru["ru_utime.tv_usec"]/1000)) - ($rus["ru_utime.tv_sec"]*1000 + intval($rus["ru_utime.tv_usec"]/1000));
		} else {
			$this->_stopttime = microtime(true);
			$this->_totaltime = ($time_end - $time_start);
		}
	}

	public function time() {
		return $this->_totaltime;
	}
}