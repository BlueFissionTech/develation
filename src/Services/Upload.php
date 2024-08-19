<?php
namespace BlueFission\Services;

class Upload {
	
	public function __construct($file)
	{
		$this->file = $file;
	}

	public function save($path)
	{
		if (move_uploaded_file($this->file['tmp_name'], $path)) {
			return true;
		} else {
			return false;
		}
	}

	public function path()
	{
		return $this->file['name'];
	}
}