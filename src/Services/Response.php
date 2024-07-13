<?php
namespace BlueFission\Services;

use BlueFission\Num;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Behavioral\Behaviors\Event;

/**
 * Class Response
 *
 * The Response class is used to handle the HTTP response for a web request.
 * It extends the Obj class to include the ability to dispatch events.
 */
class Response extends Obj
{
	/**
	 * Max depth for filling values into the Response object
	 */
	const MAX_DEPTH = 2;

	/**
	 * Max number of iterations for filling values into the Response object
	 */
	const MAX_ITERATIONS = 10;

	/**
	 * Message to be sent in the HTTP response
	 *
	 * @var string
	 */
	protected $_message;

	/**
	 * Data to be included in the HTTP response
	 *
	 * @var array
	 */
	protected $_data = [
		'id'=>'',
		'list'=>'',
		'data'=>'',
		'children'=>'',
		'status'=>'',
		'info'=>'',
	];

	/**
	 * Fill the Response object with values from an input array.
	 *
	 * @param array $values Values to be filled into the Response object
	 * @param int $depth Depth of the fill operation (default 0)
	 * @return void
	 */
	public function fill( $values, $depth = 0 )
	{
		if ( $depth > self::MAX_DEPTH ) {
			return;
		}
		if ( \Arr::is($values) ) {
			$mapped = false;
			$iterations = 0;
			foreach ( $values as $key=>$value ) {
				if ( $iterations > self::MAX_ITERATIONS ) {
					break;
				}

				if ( $depth == 0 && $this->_data->hasKey($key) && $this->$key == null ) {
					$mapped = true;
					$this->$key = $value;
				} else {
					$this->fill( $value, $depth+1 );
				}

				$iterations++;
			}

			if ( $depth == 0 && Arr::isAssoc($values) && $this->data == null && $values != $this->list ) {
				$this->data = $values;
			}

			if ( $depth == 0 && Arr::isIndexed($values) && $mapped == false && $this->list == null ) {
				$this->list = $values;
			}

			if ( $depth == 1 && Arr::isIndexed($values) && $this->children == null && $values != $this->list ) {
				$this->children = $values;
			} 
		}

		if ( $depth < 2 && Num::is($values) && $this->id == null  ) {
			$this->id = $values;
		}

		if ( $depth < 2 && Str::is($values) && $this->status == null  ) {
			$this->status = $values;
		}

		if ( $depth < 2 && \is_object($values) && $this->data == null  ) {
			$this->data = $values;
		}
	}

	/**
	 * Encodes the data into a json string and dispatches the complete event.
	 * 
	 * @return void
	 */
	public function send()
	{
		$this->_message = $this->_data->toJson();
		$this->dispatch( Event::COMPLETE );
	}

	/**
	 * Outputs the json string message and terminates the script execution.
	 * 
	 * @return void
	 */
	public function deliver() 
	{
		echo $this->_message ?? '{}';
		exit;
	}

	/**
	 * Returns the json string message.
	 * 
	 * @return string The json string message.
	 */
	public function message()
	{
		return $this->_message;
	}

	/**
	 * Initializes the object. Registers the deliver method to handle the complete event.
	 * 
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		$this->behavior(Event::COMPLETE, array($this, 'deliver'));
	}

}