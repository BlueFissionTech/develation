<?php
namespace BlueFission\Intelligence\Strategies;

class Strategy {
	protected $_success;
	protected $_buffer;
	protected $_prediction;
	protected $_guesses;
	protected $_rules;

	public function __construct() {
		$this->_buffer = array();
		$this->_rules = array();
		$this->_success = 0;
		$this->_prediction = 0;
		$this->_totaltime = 0;
	}

	public function train() {
		$pattern = func_get_args();

		$this->_rules = $pattern;
	}

	public function process( $val ) {
		$this->_guesses++;

		if ($this->_prediction == $val) {
			$this->_success++;
		} else {
			$this->_buffer = array();
		}
		$this->_buffer[] = $val;

		$this->_prediction = $key;
	}

	public function guess() {
		return $this->_prediction;
	}

	public function score() {
		$score = $this->_success / $this->_guesses;
		return $score;
	}
}