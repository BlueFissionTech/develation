<?php
namespace BlueFission\HTML;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Flag;
use BlueFission\IObj;
use BlueFission\HTML\HTML;
use BlueFission\Utils\Util;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Data\FileSystem;
use BlueFission\Data\Storage\Disk;
use BlueFission\Parsing\Parser;
use BlueFission\Parsing\Registry\TagRegistry;
use BlueFission\Parsing\Registry\RendererRegistry;
use BlueFission\Parsing\Registry\ExecutorRegistry;
use \InvalidArgumentException;

/**
 * Class Template
 *
 * This class provides functionality for handling templates. It extends the Configurable class to handle configuration.
 */
class Template extends Obj {
	use Configurable {
		Configurable::__construct as private __configConstruct;
	}

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
     * $_placeholders full text for the template placeholder tags
     * @var array
     */
    private $_placeholders;

    private $_placeholderNames;

    private $_conditions;
    /**
     * @var array $_config Default configuration for the Template class
     */
    protected $_config = array(
        'file'=>'',
        'template_directory'=>'',
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
        parent::__construct();
        if ( Val::isNotNull( $config ) && Str::is($config)) {
            $config = ['file'=>$config];
        }

    	$this->__configConstruct($config);
    	$this->load();
        
        $this->_cached = false;

        $this->dispatch( State::DRAFT );
    }

    /**
     * Loads the contents of a file into the `$_template` property.
     *
     * @param null|string $file The file to load
     */
    public function load ( $file = null ): IObj
    {
    	$this->perform( State::READING );

    	$file = $file ?? $this->config('file');

    	if ( Val::isEmpty($file) ) {
    		$status = 'No file specified';
    		$this->status($status);
    		$this->perform( Event::ERROR, new Meta(info: $status, src: $this, when: State::READING ) );
    		$this->halt(State::READING);

			return $this;
		}

    	$info = pathinfo($file);

    	$file = $info['basename'];

    	$path_r = [];
    	$path_r[] = $this->config('template_directory');
    	$path_r[] = $info['dirname'];
    	$path = implode(DIRECTORY_SEPARATOR, $path_r);

        if ( Val::isNotNull($file)) {
            $this->_file = (new FileSystem(['root'=>$path]))->open($file);
            $this->_template = $this->_file->read()->contents();
            $this->perform( Event::READ, new Meta(info: 'File loaded', src: $this, when: State::READING ) );
        }

    	$this->halt(State::READING);

        return $this;
    }

    /**
     * Gets or sets the contents of the `$_template` property.
     *
     * @param null|string $data The new value for `$_template`
     * @return string The contents of `$_template`
     */
	public function contents($data = null)
	{
		if (Val::isNull($data)) return $this->_template;
		
		$this->_template = $data;
	}
	
	/**
	 * public function clear()
	 * This method clears the content of the template and resets it.
	 */
	public function clear(): IObj
	{
		parent::clear();
		$this->reset();

		return $this;
	}

	/**
	 * public function reset()
	 * This method resets the template to its original state.
	 */
	public function reset(): IObj
	{
		$this->load();

		return $this;
	}

	/**
	 * public function set( $var, $content = null, $formatted = null, $repetitions = null )
	 * This method sets the content of the template.
	 * @param  mixed  $var        the variable name or data
	 * @param  mixed  $content    the content to be assigned to the variable
	 * @param  mixed  $formatted  specifies if the content should be formatted as HTML or not
	 * @param  mixed  $repetitions  specifies the number of repetitions for the content
	 */
	public function set( $var, $content = null, $formatted = null, $repetitions = null  ): IObj
	{
		if ($formatted)
			$content = HTML::format($content);

		if (Str::is($var)) {
			if ( Val::isNotNull($formatted) && !Flag::is($formatted) ) {
				throw new InvalidArgumentException( 'Formatted argument expects boolean');
			}

			if ( Str::is($content) ) {
				$this->_template = str_replace( $this->config('delimiter_start') . $var . $this->config('delimiter_end'), $content, $this->_template, $repetitions );
			} elseif ( is_object( $content ) || Arr::isAssoc( $content ) )	{
				foreach ($content as $a=>$b) 
				{
					$this->set($var.'.'.$a, $b, $formatted, $repetitions);
				}
				$this->field($var, $content);
			}
			elseif ( Arr::is($content) )
			{
				$this->field($var, $content);
			}
		} elseif ( is_object( $var ) || Arr::isAssoc( $var ) ) {

			if ( $formatted == null ) {
				$formatted = $content;
			}

			foreach ($var as $a=>$b) 
			{
				$this->set($a, $b, $formatted, $repetitions);
			}
		} else {
			throw new InvalidArgumentException( 'Invalid property' );
		}

		return $this;
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

		if (Str::is($var))
		{
			if ( !$content )
			{
				throw new InvalidArgumentException( 'Cannot assign empty value.');
			}

			if ( Val::isNotNull($formatted) && !Flag::is($formatted) )
			{
				throw new InvalidArgumentException( 'Formatted argument expects boolean');
			}

			return parent::field($var, $content );
		}
		elseif ( is_object( $var ) || Arr::isAssoc( $var ) )
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
	 * @return IObj
	 */
	public function assign( $data, $formatted = null ): IObj
	{
		$this->field($data, $formatted);

		return $this;
	}

	/**
	 * Cache the contents of the template
	 *
	 * @param int $minutes The number of minutes to cache the template
	 *
	 * @return IObj
	 */
	public function cache ( $minutes = null ): IObj
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
		if (Val::isNull($value))
			return $this->_cached;
		$this->_cached = ($value == true);
	}

	/**
	 * Method to commit the data and formatting to the template
	 *
	 * @param mixed $formatted The formatting to apply to the data, if any
	 */
	public function commit( $formatted = null ): IObj
	{
		$this->set( $this->_data->val(), $formatted );

		return $this;
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
		if (Val::isNull($formatted)) {
			$formatted = true;
		}
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
		// $this->executeModules();
		// $this->applyTemplate();
		// $this->commit( $this->config('format') );
		
		TagRegistry::registerDefaults(); // ensure tags are active
		RendererRegistry::registerDefaults(); // ensure renderers are active
		ExecutorRegistry::registerDefaults(); // ensure executors are active
		
		$parser = new Parser($this->_template, $this->config('delimiter_start'), $this->config('delimiter_end'));

		$parser->setVariables($this->_data->val());

		$includeDirs = array_filter([
			'templates' => $this->config('template_directory'),
			'modules' => $this->config('module_directory'),
		]);

		$parser->setIncludePaths($includeDirs);

		$output = $parser->render();

		if ($this->config('eval')) {
			ob_start();
			eval('?>' . $output . '<?php');
			return ob_get_clean();
		}

		return $output;
	}

	/**
	 * Method to publish the rendered output to the screen
	 */
	public function publish ( ) 
	{
		print($this->render());
	}
}