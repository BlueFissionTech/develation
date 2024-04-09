<?php
namespace BlueFission\Data;

use BlueFission\IObj;
use BlueFission\Obj;
use BlueFission\Behavioral\Configurable;

/**
 * Class Data
 *
 * @package BlueFission\Data
 * 
 * The Data class extends the Obj class and implements the IData interface.
 * This class is used to manage data objects and their properties.
 */
class Data extends Obj implements IData
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
     * @return IObj
     */
    public function read(): IObj 
    {
        // method implementation
    }
    
    /**
     * This method is used to write data.
     *
     * @return IObj
     */
    public function write(): IObj 
    {
        // method implementation
    }
    
    /**
     * This method is used to delete data.
     *
     * @return IObj
     */
    public function delete(): IObj 
    {
        // method implementation
    }
    
    /**
     * This method is used to get the contents of data.
     *
     * @return mixed
     */
    public function contents($data = null): mixed
    {
        // method implementation
    }

    /**
     * This method is used to get the data.
     *
     * @return mixed
     */
    public function data(): mixed
    {
        return $this->_data->val();
    }
    
    /**
     * This method is used to register the input variables from various sources as global variables.
     *
     * @param string $source
     *
     * @return IObj
     */
    public function registerGlobals( string $source = null ): IObj
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

        return $this;
    }
}