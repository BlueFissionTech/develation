<?php
namespace BlueFission\Data\Storage\Structure;

use BlueFission\DevString;

class MysqlField {

	private $_name;
	private $_type;
	private $_size;
	private $_primary;
	private $_unique;
	private $_null;
	private $_binary;
	private $_foreign = [];
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

	public function autoincrement( $isTrue = true)
	{
		$this->_autoincrement = $isTrue;

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

		if ( $this->_size ) {
			$definition[] = "({$this->_size})";
		}
		
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

	public function extras()
	{
		$extras = [];

		if ( $this->_primary ) {
			$extras[] = "PRIMARY KEY (`{$this->_name}`)";
		}

		if ( $this->_unique ) {
			$extras[] = "UNIQUE INDEX `{$this->_name}_UNIQUE` (`{$this->_name}` ASC) VISIBLE";
		}

		if ( count($this->_foreign) > 0 ) {
			foreach ( $this->_foreign as $entity => $values ) {
				$extras[] = "INDEX `{$this->_name}_idx` (`{$this->_name}` ASC) VISIBLE";
				$foreign = "CONSTRAINT `{$this->_name}_".DevString::random(null, 4)."`\n".
				    "FOREIGN KEY (`{$this->_name}`)\n".
				    "REFERENCES `$entity` (`{$values['on']}`)\n";

				    if ( $values['delete'] ) {
				    	$foreign .= " ON DELETE CASCADE\n";
				    }

				    if ( $values['update'] ) {
				    	$foreign .= " ON UPDATE CASCADE\n";
				    }

				$extras[] = $foreign;
			}
		}


		$extras_string = implode(",\n", $extras);

		return $extras_string;
	}

	public function additions()
	{
		$additions = [];

		$addition_string = implode(",\n", $additions);

		return $addition_string;
	}
}