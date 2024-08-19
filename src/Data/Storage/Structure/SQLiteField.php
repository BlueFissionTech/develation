<?php
namespace BlueFission\Data\Storage\Structure;

use BlueFission\Connections\Database\SQLiteLink;
use BlueFission\Str;

/**
 * Class SQLiteField 
 * Represents a field in a SQLite table. 
 */
class SQLiteField {
    /**
     * @var string The name of the field.
     */
    private $name;

    /**
     * @var string The type of the field.
     */
    private $type;

    /**
     * @var int The size of the field.
     */
    private $size;

    /**
     * @var boolean If the field is a primary key.
     */
    private $primary;

    /**
     * @var boolean If the field is unique.
     */
    private $unique;

    /**
     * @var boolean If the field can be null.
     */
    private $null;

    /**
     * @var mixed The default value of the field.
     */
    private $default;

    /**
     * @var boolean If the field is auto-incremented.
     */
    private $autoIncrement;

    /**
     * Constructor for the SQLiteField class.
     *
     * @param string $name The name of the field.
     *
     * @return SQLiteField 
     */
    public function __construct($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the type of the field.
     *
     * @param string $type The type of the field.
     *
     * @return SQLiteField 
     */
    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the size of the field.
     *
     * @param int $size The size of the field.
     *
     * @return SQLiteField 
     */
    public function size($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Sets the field as a primary key.
     *
     * @param boolean $isTrue If the field is a primary key.
     *
     * @return SQLiteField 
     */
    public function primary( $isTrue = true)
    {
        $this->primary = $isTrue;

        return $this;
    }

    /**
     * Sets the field as auto-incremented.
     *
     * @param boolean $isTrue If the field is auto-incremented.
     *
     * @return SQLiteField 
     */
    public function autoincrement( $isTrue = true)
    {
        $this->autoIncrement = $isTrue;

        return $this;
    }

    /**
     * Sets the field as unique.
     *
     * @param boolean $isTrue If the field is unique.
     *
     * @return SQLiteField 
     */
    public function unique( $isTrue = true)
    {
        $this->unique = $isTrue;

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
		$this->null = $isTrue;

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
		$this->default = $value;

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
		$this->null = !$isTrue;

		return $this;
	}

	/**
	 * Get the definition string of the field
	 * 
	 * @return string The definition string of the field
	 */
	public function definition()
	{
		$definition[] = "`{$this->name}`";
		
		switch ($this->type) {
			case 'datetime':
			$definition[] = "DATETIME";
			break;

			case 'date':
			$definition[] = "DATE";
			break;

			case 'boolean':
			$definition[] = "INTEGER";
			break; 

			case 'numeric':
			$definition[] = "INTEGER";
			break;

			case 'decimal':
			$definition[] = "REAL";
			break;

			default:
			case 'text':
			$definition[] = "TEXT";
			break;
		}

		if ($this->default !== null) {
			$definition[] = "DEFAULT " . SQLiteLink::sanitize((string)$this->default);
		}
		
		if (!$this->null) {
			$definition[] = "NOT";
		}

		$definition[] = "NULL";

		if ($this->autoIncrement) {
			$definition[] = "AUTOINCREMENT";
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

		if ($this->primary) {
			$extras[] = "PRIMARY KEY (`{$this->name}`)";
		}

		if ($this->unique) {
			$extras[] = "UNIQUE (`{$this->name}`)";
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
