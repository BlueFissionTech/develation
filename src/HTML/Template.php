<?php
namespace BlueFission\HTML;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\HTML\HTML;
use BlueFission\Utils\Util;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Data\FileSystem;
use BlueFission\Data\Storage\Disk;
use \InvalidArgumentException;

/**
 * Class Template
 *
 * This class provides functionality for handling templates. It extends the Configurable class to handle configuration.
 */
class Template extends Configurable {
    /**
     * @var string $_template The contents of the template file
     */
    private $_template;
    /**
     * @var bool $_cached Whether the template is cached
     */
    private $_cached;
    /**
     * @var FileSystem $_file An object to represent the file system
     */
    private $_file;
    /**
     * @var array $_config Default configuration for the Template class
     */
    protected $_config = array(
        'file'=>'',
        'cache'=>true,
        'cache_expire'=>60,
        'cache_directory'=>'cache',
        'max_records'=>1000, 
        'delimiter_start'=>'{', 
        'delimiter_end'=>'}',
        'module_token'=>'mod', 
        'module_directory'=>'modules',
        'format'=>false,
        'eval'=>false,
    );

    /**
     * Template constructor.
     *
     * @param null|array $config Configuration for the Template class
     */
    public function __construct ( $config = null ) 
    {
        parent::__construct( $config );
        if ( DevValue::isNotNull( $config ) ) {
            if (DevArray::isAssoc($config)) {
                $this->config($config);
                $this->load($this->config('file'));
            } else {
                $this->load($config);
            }
        }
        $this->_cached = false;

        $this->dispatch( State::DRAFT );
    }

    /**
     * Loads the contents of a file into the `$_template` property.
     *
     * @param null|string $file The file to load
     */
    public function load ( $file = null ) 
    {
        if ( DevValue::isNotNull($file)) {
            $this->_file = new FileSystem($file);
            // $this->_file->open();
        }
        if ( $this->_file ) {
            $this->_file->read($file);
            $this->_template = $this->_file->contents();
        }
    }

    /**
     * Gets or sets the contents of the `$_template` property.
     *
     * @param null|string $data The new value for `$_template`
     * @return string The contents of `$_template`
     */
	public function contents($data = null)
	{
		if (DevValue::isNull($data)) return $this->_template;
		
		$this->_template = $data;
	}
	
	/**
	 * public function clear()
	 * This method clears the content of the template and resets it.
	 */
	public function clear (): void
	{
		parent::clear();
		$this->reset();
	}

	/**
	 * public function reset()
	 * This method resets the template to its original state.
	 */
	public function reset()
	{
		$this->load();
	}

	/**
	 * public function set( $var, $content = null, $formatted = null, $repetitions = null )
	 * This method sets the content of the template.
	 * @param  mixed  $var        the variable name or data
	 * @param  mixed  $content    the content to be assigned to the variable
	 * @param  mixed  $formatted  specifies if the content should be formatted as HTML or not
	 * @param  mixed  $repetitions  specifies the number of repetitions for the content
	 */
	public function set( $var, $content = null, $formatted = null, $repetitions = null  ) 
	{
		if ($formatted)
			$content = HTML::format($content);

		if (is_string($var))
		{
			if ( DevValue::isNotNull($formatted) && !is_bool($formatted) )
			{
				throw new InvalidArgumentException( 'Formatted argument expects boolean');
			}

			if ( is_string($content) )
			{
				$this->_template = str_replace ( $this->config('delimiter_start') . $var . $this->config('delimiter_end'), $content, $this->_template, $repetitions );
			}
			elseif ( is_object( $content ) || DevArray::isAssoc( $content ) )
			{
				foreach ($content as $a=>$b) 
				{
					$this->set($var.'.'.$a, $b, $formatted, $repetitions);
				}
				$this->field($var, $content);
			}
			elseif ( is_array($content) )
			{
				$this->field($var, $content);
			}
		}
		elseif ( is_object( $var ) || DevArray::isAssoc( $var ) )
		{

			if ( $formatted == null )
				$formatted = $content;

			foreach ($var as $a=>$b) 
			{
				$this->set($a, $b, $formatted, $repetitions);
			}
		}
		else
		{
			throw new InvalidArgumentException( 'Invalid property' );
		}
	}

	/**
	 * Set the content of a field in the template
	 *
	 * @param mixed $var The field name or an associative array of field names and contents
	 * @param mixed $content The content of the field. If $var is an associative array, this argument will be used as the value of $formatted
	 * @param mixed $formatted Whether to format the content as HTML. Expects a boolean value
	 *
	 * @throws InvalidArgumentException If $content is empty or $formatted is not boolean
	 * @return mixed The content of the field
	 */
	public function field( string|object|array $var, $content = null, $formatted = null ): mixed
	{
		if ($formatted) {
			$content = HTML::format($content);
		}

		if (is_string($var))
		{
			if ( !$content )
			{
				throw new InvalidArgumentException( 'Cannot assign empty value.');
			}

			if ( DevValue::isNotNull($formatted) && !is_bool($formatted) )
			{
				throw new InvalidArgumentException( 'Formatted argument expects boolean');
			}

			return parent::field($var, $content );
		}
		elseif ( is_object( $var ) || DevArray::isAssoc( $var ) )
		{

			if ( !$formatted )
				$formatted = $content;

			foreach ($var as $a=>$b) 
			{
				$this->field($a, $b, $formatted);
			}
		}
		else
		{
			throw new InvalidArgumentException( 'Invalid property' );
		}

		return true;
	}

	/**
	 * Set the content of the fields in the template
	 *
	 * @param mixed $data The content of the fields. Expects an associative array of field names and contents
	 * @param mixed $formatted Whether to format the content as HTML. Expects a boolean value
	 *
	 * @return void
	 */
	public function assign( $data, $formatted = null )
	{
		$this->field($data, $formatted);
	}

	/**
	 * Cache the contents of the template
	 *
	 * @param int $minutes The number of minutes to cache the template
	 *
	 * @return void
	 */
	public function cache ( $minutes = null ) 
	{
		$file = $this->config('cache_directory').DIRECTORY_SEPARATOR.$_SERVER['REQUEST_URI'];
		if (file_exists($file) && filectime($file) <= strtotime("-{$time} minutes")) {
			$this->_cached = true;
			$this->load ( $file );
		}
		else
		{
			$copy = new Disk( array('name'=>$file) );
			$copy->contents($this->_template);
			$copy->write();
		}
	}

	/**
	 * Check if the contents of the template are cached
	 *
	 * @param mixed $value If set, sets the cached property to the value
	 *
	 * @return mixed The value of the cached property
	 */
	private function cached ( $value ) 
	{
		if (DevValue::isNull($value))
			return $this->_cached;
		$this->_cached = ($value == true);
	}

	/**
	 * Method to commit the data and formatting to the template
	 *
	 * @param mixed $formatted The formatting to apply to the data, if any
	 */
	public function commit( $formatted = null )
	{
		$this->set( $this->_data, $formatted );
	}

	/**
	 * Method to render a set of records
	 *
	 * @param array $recordSet The set of records to be rendered
	 * @param mixed $formatted The formatting to apply to the records, if any
	 *
	 * @return string The rendered output
	 */
	public function renderRecordSet( $recordSet, $formatted = null ) 
	{
		$output = '';
		$count = 0;
		if (DevValue::isNull($formatted)) $formatted = true;
		foreach ($recordSet as $a) {
			$this->clear();
			$this->set($a, $formatted);
			$output .= $this->render();
			Util::parachute($count, $this->config('max_records'));
		}
		return $output;
	}

	/**
	 * Method to render the current template and its data
	 *
	 * @return string The rendered output
	 */
	public function render ( ) 
	{
		$this->executeModules();
		$this->commit( $this->config('format') );
		ob_start();
		if ($this->config('eval'))
			eval ( ' ?> ' . $this->_template . ' <?php ' );
		else
			echo $this->_template;
			
		return ob_get_clean();
	}

	/**
	 * Method to publish the rendered output to the screen
	 */
	public function publish ( ) 
	{
		print($this->render());
	}

	/**
	 * Private method to execute any modules found in the template
	 */
	private function executeModules()
	{
		$pattern = "/@".$this->config('module_token')."\('(.*)'\)/";

		preg_match_all( $pattern, $this->_template, $matches );

		for ($i = 0; $i < count($matches[0]); $i++) {
			$match = $matches[0][$i];
			$file = $matches[1][$i];
			$template = new Template();
			$template->load( $this->config('module_directory').DIRECTORY_SEPARATOR.$file);
			$content = $template->render();
			$this->_template = str_replace($match, $content, $this->_template);
		}

	}

}