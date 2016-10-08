<?php

namespace BlueFission\Intelligence;

use BlueFission\Intelligence\Collections\OrganizedCollection as Collection;
use BlueFission\Intelligence\Behaviors\OrganizedBehaviorCollection as BehaviorCollection;
use BlueFission\Intelligence\Behaviors\OrganizedHandlerCollection as HandlerCollection;
use BlueFission\Services\Application;

class Intelligence extends Application {

	public function __construct() 
	{
		parent::__construct();
		$this->_services = new Collection();
		$this->_routes = new Collection();
		$this->_behaviors = new BehaviorCollection();
		$this->_handlers = new HandlerCollection();
	}
}