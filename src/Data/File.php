<?php
namespace BlueFission\Data;

use BlueFission;
use BlueFission\Collections\Hierarchical;

/**
 * Class File
 *
 * @package BlueFission\Data
 */
class File extends Hierarchical
{
    /**
     * @var string $_contents Store the contents of the file
     */
    private $_contents;

    /**
     * Constant for path separator
     */
    const PATH_SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * File constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get or set the contents of the file
     *
     * @param string|null $data
     *
     * @return mixed
     */
    public function contents($data = null): mixed
    {
        if (Val::isNull($data)) {
            return $this->_contents;
        }

        $this->_contents = $data;
    }

    /**
     * Append data to the contents of the file
     *
     * @param string $data
     */
    public function append($data): IObj
    {
        $this->_contents .= $data;

        return $this;
    }

    /**
     * Write the contents of the file to storage
     */
    public function write(): IObj
    {
        if ( method_exists($this->_root, 'write') ) // or is callable?
        {
            $storage = new ReflectionClass( get_class( $this->_root ) );

            $this->_root->config( $storage->getStaticPropertyValue('NAME_FIELD'), $this->_label );
            $this->_root->config( $storage->getStaticPropertyValue('PATH_FIELD'), implode( $storage->getStaticPropertyValue('PATH_SEPARATOR'), $this->path() ) );
            $this->_root->contents( $this->contents() );
            $this->_root->write();
        }

        return $this;
    }
}
