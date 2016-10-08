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

	protected $_message;

	protected $_data = array(
		'id'=>'',
		'list'=>'',
		'data'=>'',
		'children'=>'',
		'status'=>'',
		'info'=>'',
	);

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

	protected function init()
	{
		parent::init();

		$this->behavior( Event::COMPLETE, array($this, 'deliver'));
		// $this->halt( State::DRAFT );
	}
}