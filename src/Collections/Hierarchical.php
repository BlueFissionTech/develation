<?php
namespace BlueFission\Collections;

class Hierarchical extends Collection implements ICollection
{
	/**
	 * Root object of the hierarchy
	 *
	 * @var object
	 */
	protected $_root;

	/**
	 * Parent object in the hierarchy
	 *
	 * @var object
	 */
	protected $_parent;

	/**
	 * Label for the current object in the hierarchy
	 *
	 * @var string
	 */
	protected $_label;
	
	/**
	 * Constant string used to separate elements of a hierarchy path
	 */
	const PATH_SEPARATOR = '.';

	/**
	 * Initializes a new instance of the class
	 *
	 * @return void
	 */
	public function __construct( ) {
		parent::__construct();
		$this->_root = null;
	}

	/**
	 * Gets or sets the label for the current object in the hierarchy
	 *
	 * @param string|null $label
	 * @return string|null
	 */
	public function label( $label = null ) {
		if ( is_scalar($label) ) $this->_label = $label;
		return $this->_label;
	}

	/**
	 * Gets or sets the parent object in the hierarchy
	 *
	 * @param Hierarchical|null $parent
	 * @return Hierarchical|null
	 */
	public function parent( $parent = null )
	{
		if ( $parent instanceof Hierarchical ) {
			$this->_parent = $parent;
		}
		return $this->_parent;
	}

	/**
	 * Adds an object to the hierarchy
	 *
	 * @param Hierarchical $object
	 * @param string|null $label
	 * @return ICollection
	 */
	public function add( $object, $label = null): ICollection
	{
		$object->parent($this);
		$label = $object->label($label);
		return parent::add($object, $label);
	}

	/**
	 * Gets the path of the object in the hierarchy
	 *
	 * @return array
	 */
	public function path() {
		$path = $this->_parent ? $this->_parent->path() : array();

		$path[] = $this->label(); 
		
		return $path;
	}

	/**
	 * Gets the contents of the hierarchy
	 *
	 * @return array
	 */
	public function contents()
	{
		return $this->_value->contents();
	}

}
