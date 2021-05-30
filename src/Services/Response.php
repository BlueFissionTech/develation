<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Dispatcher;
use BlueFission\Net\HTTP;
use BlueFission\DevArray;
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

				if ( $depth == 0 && \array_key_exists($key, $this->_data) && $this->$key == null ) {
					$mapped = true;
					$this->$key = $value;
				} else {
					$this->fill( $value, $depth+1 );
				}

				$iterations++;
			}

			if ( $depth == 0 && DevArray::isAssoc($values) && $this->data == null && $values != $this->list ) {
				$this->data = $values;
			}

			if ( $depth == 0 && DevArray::isIndexed($values) && $mapped == false && $this->list == null ) {
				$this->list = $values;
			}

			if ( $depth == 1 && DevArray::isIndexed($values) && $this->children == null && $values != $this->list ) {
				$this->children = $values;
			} 
		}

		if ( $depth < 2 && \is_numeric($values) && $this->id == null  ) {
			$this->id = $values;
		}

		if ( $depth < 2 && \is_string($values) && $this->status == null  ) {
			$this->status = $values;
		}

		if ( $depth < 2 && \is_object($values) && $this->data == null  ) {
			$this->data = $values;
		}

		// var_dump($this->_data);
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