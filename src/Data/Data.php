<?php
namespace BlueFission\Data;

use BlueFission\DevObject;
use BlueFission\Behavioral\Configurable;

/**
 * Class Data
 *
 * @package BlueFission\Data
 * 
 * The Data class extends the DevObject class and implements the IData interface.
 * This class is used to manage data objects and their properties.
 */
class Data extends DevObject implements IData
{
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }

    public function __construct( $config = null )
    {
        $this->__configConstruct($config);
        parent::__construct();
    }
    
    /**
     * This method is used to read data.
     *
     * @return void
     */
    public function read() 
    {
        // method implementation
    }
    
    /**
     * This method is used to write data.
     *
     * @return void
     */
    public function write() 
    {
        // method implementation
    }
    
    /**
     * This method is used to delete data.
     *
     * @return void
     */
    public function delete() 
    {
        // method implementation
    }
    
    /**
     * This method is used to get the contents of data.
     *
     * @return void
     */
    public function contents() 
    {
        // method implementation
    }

    /**
     * This method is used to get the data.
     *
     * @return mixed
     */
    public function data() 
    {
        return $this->_data;
    }
    
    /**
     * This method is used to register the input variables from various sources as global variables.
     *
     * @param string $source
     *
     * @return void
     */
    public function registerGlobals( string $source = null )
    {
        $source = strtolower($source);
        switch( $source )
        {
            case 'post':
                $vars = filter_input_array(INPUT_POST);
            break;
            case 'get':
                $vars =  filter_input_array(INPUT_GET);
            break;
            case 'session':
                $vars = filter_input_array(INPUT_SESSION);
            break;
            case 'cookie':
            case 'cookies':
                $vars = filter_input_array(INPUT_COOKIE);
            break;
            default:
            case 'globals':
                $vars = $GLOBALS;
            break;
            case 'request':
                $vars = filter_input_array(INPUT_REQUEST);
            break;
        }

        $this->assign($vars);
    }
}