<?php
namespace BlueFission\Data\Storage\Structure;

use BlueFission\Connections\Database\MysqlLink;
use BlueFission\DevString;

/**
 * Class MysqlField 
 * Represents a field in a MySQL table. 
 */
class MysqlField {
    /**
     * @var string The name of the field.
     */
    private $_name;

    /**
     * @var string The type of the field.
     */
    private $_type;

    /**
     * @var int The size of the field.
     */
    private $_size;

    /**
     * @var boolean If the field is a primary key.
     */
    private $_primary;

    /**
     * @var boolean If the field is unique.
     */
    private $_unique;

    /**
     * @var boolean If the field can be null.
     */
    private $_null;

    /**
     * @var mixed The default value of the field.
     */
    private $_default;

    /**
     * @var boolean If the field is a binary.
     */
    private $_binary;

    /**
     * @var array Contains the foreign key information for the field.
     */
    private $_foreign = [];

    /**
     * @var boolean If the field is auto-incremented.
     */
    private $_autoincrement;

    /**
     * @var MysqlLink a connection to the target database.
     */
    private $_link;

    /**
     * Constructor for the MysqlField class.
     *
     * @param string $name The name of the field.
     *
     * @return MysqlField 
     */
    public function __construct($name)
    {
        $this->_name = $name;
        $this->_link = new MysqlLink();

        return $this;
    }

    /**
     * Sets the type of the field.
     *
     * @param string $type The type of the field.
     *
     * @return MysqlField 
     */
    public function type($type)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * Sets the size of the field.
     *
     * @param int $size The size of the field.
     *
     * @return MysqlField 
     */
    public function size($size)
    {
        $this->_size = $size;

        return $this;
    }

    /**
     * Sets the field as a primary key.
     *
     * @param boolean $isTrue If the field is a primary key.
     *
     * @return MysqlField 
     */
    public function primary( $isTrue = true)
    {
        $this->_primary = $isTrue;

        return $this;
    }

    /**
     * Sets the field as auto-incremented.
     *
     * @param boolean $isTrue If the field is auto-incremented.
     *
     * @return MysqlField 
     */
    public function autoincrement( $isTrue = true)
    {
        $this->_autoincrement = $isTrue;

        return $this;
    }

    /**
     * Sets the field as unique.
     *
     * @param boolean $isTrue If the field is unique.
     *
     * @return MysqlField 
     */
    public function unique( $isTrue = true)
    {
        $this->_unique = $isTrue;

		return $this;
	}

	/**
	 * Set the null property of the field
	 * 
	 * @param bool $isTrue Whether the field should be set to null
	 * @return object Returns the instance of the class
	 */
	public function null( $isTrue = true)
	{
		$this->_null = $isTrue;

		return $this;
	}

	/**
	 * Set the default value of the field
	 * 
	 * @param mixed $value the default value of the field
	 * @return object Returns the instance of the class
	 */
	public function default( mixed $value )
	{
		$this->_default = $value;

		return $this;
	}

	/**
	 * Set the required property of the field
	 * 
	 * @param bool $isTrue Whether the field is required
	 * @return object Returns the instance of the class
	 */
	public function required( $isTrue = true)
	{
		$this->_null = !$isTrue;

		return $this;
	}

	/**
	 * Set the foreign property of the field
	 * 
	 * @param string $entity The entity referenced by the foreign key
	 * @param string $onField The field referenced in the foreign entity
	 * @param string $updateAction The action to be taken on update of the foreign field
	 * @param string $deleteAction The action to be taken on delete of the foreign field
	 * @return object Returns the instance of the class
	 */
	public function foreign( $entity, $onField = 'id', $updateAction = '', $deleteAction = '' )
	{
		$this->_foreign[$entity] = ['on'=>$onField, 'update'=>$updateAction, 'delete'=>$deleteAction];

		return $this;
	}

	/**
	 * Get the definition string of the field
	 * 
	 * @return string The definition string of the field
	 */
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

			case 'boolean':
			$definition[] = "TINYINT";
			break; 

			case 'numeric':
			$definition[] = "INT";
			break;

			case 'decimal':
			$definition[] = "DECIMAL";
			break;

			case 'tinytext':
			$definition[] = "TINYTEXT";
			break;

			case 'mediumtext':
			$definition[] = "MEDIUMTEXT";
			break;

			case 'longtext':
			$definition[] = "LONGTEXT";
			break;

			default:
			case 'text':
			if ($this->_size > 255) {
				$definition[] = "TEXT";
			} else {
				$definition[] = "VARCHAR";
			}
			break;
		}

		if ( $this->_size ) {
			$definition[] = "({$this->_size})";
		}

		if ( $this->_default !== null ) {
			$definition[] = "DEFAULT ".$this->_link->sanitize((string)$this->_default);
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

	/**
	 * Get the extras string of the field
	 * 
	 * @return string The extras string of the field
	 */
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

	/**
	 * Adds any additional properties to the table definition.
	 * 
	 * @return string The string representation of the additional properties to be added to the table definition.
	 */
	public function additions()
		{
			$additions = [];

			$addition_string = implode(",\n", $additions);

			return $addition_string;
		}
}