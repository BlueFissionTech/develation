<?php
namespace BlueFission\Exceptions;

class NotImplementedException extends \Exception
{
	public function NotImplementedException( $message = "" )
	{
		parent::__construct( $message );
	}
}