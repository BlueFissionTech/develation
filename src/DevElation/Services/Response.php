<?php
namespace BlueFission;
@include_once('Loader.php');
$loader = Loader::instance();
$loader->load('com.bluefission.behaviors.*');
$loader->load('com.bluefission.develation.functions.common');
$loader->load('com.bluefission.develation.functions.http');
$loader->load('com.bluefission.develation.DevModel');

class Response extends \DevModel
{

	protected $_data = array(
		'list'=>'',
		'data'=>'',
		'children'=>'',
		'status'=>'',
		'info'=>'',
	);

	public function send()
	{
		echo \dev_json_encode( $this->_data );
		$this->perform( Event::COMPLETE);
	}

	protected function init()
	{
		parent::init();
		$this->halt( State::DRAFT );
	}
}