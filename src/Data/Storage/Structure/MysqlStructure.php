<?php
namespace BlueFission\Data\Storage\Structure;

class MysqlStructure extends Structure {
	protected $_fields = [];
	protected $_comment;
	protected $_query = [];
	protected $_definitions = [];
	protected $_extras = [];
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
		$field = new MysqlField($name);
		$field->type($type)->size($size);
		$this->_fields[$name] = $field;

		return $field;
	}

	public function comment($text) {
		$this->_comment = $text;
	}

	public function primary($name, $size = 11)
	{
		$this->numeric($name, 11)->primary();
	}

	public function incrementer($name, $size = 11)
	{
		$this->numeric($name, 11)->primary()->autoincrement();
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

	public function timestamps()
	{
		$this->datetime('created');
		$this->datetime('updated');
	}

	public function build()
	{
		foreach ($this->_fields as $field) {
			$this->_definitions[] = $field->definition();
			
			$extras = $field->extras();
			if ( $extras ) {
				$this->_extras[] = $extras;
			}
			
			$additions = $field->additions();
			if ( $additions ) {
				$this->_additions[] = $additions;
			}
		}

		if ( $this->_comment ) {
			$this->_additions[] = "COMMENT='".addslashes($this->_comment)."'";
		}

		$definitions = array_merge($this->_definitions, $this->_extras);

		$this->_query[] = "(". implode(",\n", $definitions) . ")";

		$this->_query = array_merge($this->_query, $this->_additions);
		
		$query = implode("\n", $this->_query);

		$query .= ';';

		return $query;
	}
}