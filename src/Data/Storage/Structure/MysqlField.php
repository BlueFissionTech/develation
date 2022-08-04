<?php
namespace BlueFission\Data\Storage\Structure;

class MysqlField {

	private $_name;
	private $_type;
	private $_size;
	private $_primary;
	private $_unique;
	private $_null;
	private $_binary;
	private $_foreign;
	private $_autoincrement;

	public function __construct($name)
	{
		$this->_name = $name;

		return $this;
	}

	public function type($type)
	{
		$this->_type = $type;

		return $this;
	}

	public function size($size)
	{
		$this->_size = $size;

		return $this;
	}

	public function primary( $isTrue = true)
	{
		$this->_primary = $isTrue;

		return $this;
	}

	public function unique( $isTrue = true)
	{
		$this->_unique = $isTrue;

		return $this;
	}

	public function null( $isTrue = true)
	{
		$this->_null = $isTrue;

		return $this;
	}

	public function required( $isTrue = true)
	{
		$this->_null = !$isTrue;

		return $this;
	}

	public function foreign( $entity, $onField = 'id', $updateAction = '', $deleteAction = '' )
	{
		$this->_foreign[$entity] = ['on'=>$onField, 'update'=>$updateAction, 'delete'=>$deleteAction];

		return $this;
	}

	public function definition()
	{
		$definition[] = "`{$this->_name}`";
		
		switch ($this->_type) {
			case 'datetime':
			$definition[] = "DATETIME";
			break;

			case 'date':
			$definition[] = "DATE";
			break;

			case 'numeric':
			$definition[] = "INT";
			break;

			default:
			case 'text':
			$definition[] = "VARCHAR";
			break;
		}

		$definition[] = "({$this->_size})";
		
		if ( !$this->_null ) {
			$definition[] = "NOT";
		}

		$definition[] = "NULL";

		if ( $this->_autoincrement ) {
			$definition[] = "AUTO_INCREMENT";
		}

		$definition_string = implode(' ', $definition);

		return $definition_string;
	}

	public function additions
	{
		if ( $this->_primary ) {
			$additions[] = "PRIMARY KEY (`{$name}`)";
		}

		if ( count($this->_foreign) > 0 ) {
			foreach ( $this->_foreign as $entity => $values ) {
				$additions[] = "INDEX `{$name}_idx` (`{$name}` ASC) VISIBLE";
				$foreign = "CONSTRAINT `{$name}`
				    FOREIGN KEY (`{$name}`)
				    REFERENCES `$entity` (`{$values['on']}`)";

				    if ( $values['delete'] ) {
				    	$foreign .= " ON DELETE CASCADE";
				    }

				    if ( $values['update'] ) {
				    	$foreign .= " ON UPDATE CASCADE";
				    }

				$additions[] = $foreign;
			}
		}

		if ( $this->_unique ) {
			$additions[] = "UNIQUE INDEX `{$this->_name}_UNIQUE` (`{$this->_name}` ASC) VISIBLE)";
		}

		// return $additions;

		$addition_string = implode(",\n", $addition);

		return $addition_string;
	}
}