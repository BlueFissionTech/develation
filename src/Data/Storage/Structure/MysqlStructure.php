<?php
namespace BlueFission\Data\Storage\Structure;

class MysqlStructure {
	protected $_fields = [];
	protected $_comment;
	protected $_query = [];
	protected $_definitions = [];
	protected $_additions = [];

	const NUMERIC_FIELD = 'numeric';
	const TEXT_FIELD = 'text';
	const DATE_FIELD = 'date';
	const DATETIME_FIELD = 'datetime';

	public function __construct($name)
	{
		$this->_query[] = "CREATE TABLE `{$name}`";
	}

	private function newField($name, $type, $size = null)
	{
		$field = new Field($name)->type($type)->size($size);
		$this->_fields[$name] = $field;

		return $field;
	}

	public function comment($text) {
		$this->_comment = $text;
	}

	public function incrementer($name, $size = 11)
	{
		$this->numeric($name, 11);
		$this->primary($name);
	}

	public function numeric($name, $size = 11)
	{
		return $this->newField($name, self::NUMERIC_FIELD, $size);
	}

	public function text($name, $size = 45)
	{
		return $this->newField($name, self::TEXT_FIELD, $size);
	}

	public function date($name)
	{
		return $this->newField($name, self::DATE_FIELD);
	}

	public function datetime($name)
	{
		return $this->newField($name, self::DATETIME_FIELD);
	}

	// public function primary($name)
	// {
	// 	$this->_fields[$name]->primary();
	// }

	// public function unique($name)
	// {
	// 	$this->_fields[$name]->unique();
	// }

	// public function foreign($name, )
	// {
	// 	$this->_fields[$name]->foreign($entity, $onField);
	// }

	public function timestamps()
	{
		$this->datetime('created');
		$this->datetime('updated');
	}

	public function build()
	{
		foreach ($this->_fields as $field) {
			$this->_definitions[] = $field->_definition();
			$this->_additions[] = $field->_additions();
		}

		if ( $this->_comment ) {
			$additions[] = "COMMENTS='{$this->_comment}'";
		}
	}
}