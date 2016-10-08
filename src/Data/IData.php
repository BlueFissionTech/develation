<?php
namespace BlueFission\Data;

use BlueFission\IDevObject;
use BlueFission\Behavioral\IConfigurable;

interface IData extends IDevObject, IConfigurable
{
	public function read();
	public function write();
	public function delete();
	public function data();
	public function contents();
	public function status( $message = null );
}