<?php
namespace BlueFission\Data

use BlueFission\Collections\Hierarchical;
use BlueFission\Collections\ICollection;

abstract class Directory extends Hierarchical implements ICollection
{
	public function __construct( )
	{
		parent::__construct();
		$this->_root = new Storage();
	}
}