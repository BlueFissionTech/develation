<?php
namespace BlueFission\Services;

class Upload {
	
	public function __construct($file)
	{
		$this->_file = $file;
	}

	public function save($path)
	{
		if (move_uploaded_file($this->_file['tmp_name'], $path)) {
			return true;
		} else {
			return false;
		}
	}

	public function path()
	{
		return $this->_file['name'];
	}
}