<?php
namespace BlueFission\Data;

use BlueFission;
use BlueFission\Behavioral\Dispatches;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Collections\ICollection;
use BlueFission\Collections\Hierarchical;

/**
 * Class File
 *
 * @package BlueFission\Data
 */
class File extends Hierarchical implements IDispatcher
{
    use Dispatches {
        Dispatches::__construct as private __dispatchesConstruct;
    }
    /**
     * @var string $contents Store the contents of the file
     */
    private $contents;

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
        $this->__dispatchesConstruct();
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
            return $this->contents;
        }

        $this->contents = $data;

        return $this;
    }

    /**
     * Append data to the contents of the file
     *
     * @param string $data
     */
    public function append($data): ICollection
    {
        $this->contents .= $data;

        return $this;
    }

    public function read(): ICollection
    {
        if ( method_exists($this->root, 'read') ) // or is callable?
        {
            $storage = new ReflectionClass( get_class( $this->root ) );

            $this->label = $this->root->config( $storage->getStaticPropertyValue('NAME_FIELD') );
            $this->path( explode( $storage->getStaticPropertyValue('PATH_SEPARATOR'), $this->root->config( $storage->getStaticPropertyValue('PATH_FIELD') ) ) );
            $this->contents( $this->root->contents() );
        }

        return $this;
    }

    /**
     * Write the contents of the file to storage
     */
    public function write(): ICollection
    {
        if ( method_exists($this->root, 'write') ) // or is callable?
        {
            $storage = new ReflectionClass( get_class( $this->root ) );

            $this->root->config( $storage->getStaticPropertyValue('NAME_FIELD'), $this->label );
            $this->root->config( $storage->getStaticPropertyValue('PATH_FIELD'), implode( $storage->getStaticPropertyValue('PATH_SEPARATOR'), $this->path() ) );
            $this->root->contents( $this->contents() );
            $this->root->write();
        }

        return $this;
    }
}
