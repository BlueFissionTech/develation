<?php
namespace BlueFission\Services;
// @include_once('Loader.php');
// $loader = Loader::instance();
// $loader->load('com.bluefission.behaviors.*');
// $loader->load('com.bluefission.develation.functions.common');
// $loader->load('com.bluefission.develation.functions.http');
// $loader->load('com.bluefission.develation.DevModel');

use BlueFission\Behavioral\Dispatcher;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\Behaviors\Event;

class Response extends Dispatcher
{

	const MAX_DEPTH = 2;
	const MAX_ITERATIONS = 10;

	protected $_message;

	protected $_data = array(
		'id'=>'',
		'list'=>'',
		'data'=>'',
		'children'=>'',
		'status'=>'',
		'info'=>'',
	);

	public function fill( $values, $depth = 0 )
	{
		if ( $depth > self::MAX_DEPTH ) {
			return;
		}

		if ( \is_array($values) ) {
			$mapped = false;
			$iterations = 0;
			foreach ( $values as $key=>$value ) {
				if ( $iterations > self::MAX_ITERATIONS ) {
					break;
				}

				if ( $depth == 0 && \array_key_exists($key, $this->_data) && $this->$key === '' ) {
					$mapped = true;
					$this->$key = $value;
				} else {
					$this->fill( $value, ++$depth );
				}

				$iterations++;
			}

			if ( $depth == 1 && $this->children === '' ) {
				$this->children = $value;
			} 

			if ( $mapped === false && $this->list === '' ) {
				$this->list = $values;
			}

			if ( $depth == 0 && $this->data === '' ) {
				$this->data = $values;
			}
		}

		if ( \is_numeric($values) && $this->id === ''  ) {
			$this->id = $values;
		}

		if ( \is_string($values) && $this->status === ''  ) {
			$this->status = $values;
		}

		if ( \is_object($values) && $this->data === ''  ) {
			$this->data = $values;
		}
	}

	public function send()
	{
		// echo \dev_json_encode( $this->_data );
		$this->_message = HTTP::jsonEncode($this->_data);
		$this->dispatch( Event::COMPLETE);
	}

	public function deliver() 
	{
		die($this->_message);
	}

	public function message()
	{
		return $this->_message;
	}

	protected function init()
	{
		parent::init();

		$this->behavior( Event::COMPLETE, array($this, 'deliver'));
		// $this->halt( State::DRAFT );
	}
}