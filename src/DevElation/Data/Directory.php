<?php
namespace BlueFission\Data

use BlueFission\Collections\Container;
use BlueFission\Collections\ICollection;

abstract class Directory extends Container implements ICollection
{
	public function __construct( )
	{
		parent::__construct();
		$this->_root = new DevStorage();
	}
}