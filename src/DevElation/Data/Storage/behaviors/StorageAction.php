<?php
namespace BlueFission\Data\Storage\Behaviors;

use BlueFission\Behavioral\Behaviors\Action;

class StorageAction extends Action
{
	const READ = 'DoStorageRead';
	const WRITE = 'DoStorageWrite';
	const DELETE = 'DoStorageDelete';
}
