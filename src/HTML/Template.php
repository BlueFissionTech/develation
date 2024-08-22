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
     * @var string $template The contents of the template file
     */
    private $template;
    /**
     * @var bool $cached Whether the template is cached
     */
    private $cached;
    /**
     * @var FileSystem $file An object to represent the file system
     */
    private $file;

    /**
     * $placeholders full text for the template placeholder tags
     * @var array
     */
    private $placeholders;

    private $placeholderNames;

    private $conditions;
    /**
     * @var array $config Default configuration for the Template class
     */
    protected $config = array(
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
        
        $this->cached = false;

        $this->dispatch( State::DRAFT );
    }

    /**
     * Loads the contents of a file into the `$template` property.
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
            $this->file = (new FileSystem(['root'=>$path]))->open($file);
            $this->template = $this->file->read()->contents();
            $this->perform( Event::READ, new Meta(info: 'File loaded', src: $this, when: State::READING ) );
        }

    	$this->halt(State::READING);

        return $this;
    }

    /**
     * Gets or sets the contents of the `$template` property.
     *
     * @param null|string $data The new value for `$template`
     * @return string The contents of `$template`
     */
	public function contents($data = null)
	{
		if (Val::isNull($data)) return $this->template;
		
		$this->template = $data;
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
	 * parses options from template tags
	 * @param  string $str the option string
	 * @return array      the parsed options
	 */
	private function parseOptions(string $str)
    {
        $str = preg_replace("/\s*,\s*/", ",", $str); // Remove spaces surrounding commas
        $str = preg_replace("/\s*\:\s*/", ":", $str); // Remove spaces surrounding colons
        
        // Matches key-value pairs. This pattern considers the options inside single quotes, brackets and commas.
        preg_match_all("/(\w+):'([^']*)'|\d|(\w+):([^']*)|\[(.*?)\]/", $str, $matches);

        $options = [];

        // Group the matches into key-value pairs
        for ($i = 0; $i < count($matches[0]); $i++) {
            if(!empty($matches[1][$i])) {
                $options[$matches[1][$i]] = $matches[3][$i] != "" ? $matches[3][$i] : ($this->vars[$matches[2][$i]] ?? $matches[2][$i]);
                if (preg_match("/\[(.*?)\]/", $options[$matches[1][$i]], $matches2)) {
                    $value = explode(",", trim($matches2[1]));
                    $options[$matches[1][$i]] = array_map(function($v) { return trim($v, " '\""); }, $value);
                }
            } else {
                $options['options'] = explode(',', str_replace('\'', '', $matches[4][$i]));
            }
        }
        if (isset($options['stop'])) {
            $options['stop'] = str_replace('\n', "\n", $options['stop']);
        }

        return $options;
    }

    /**
     * Parses a template conditional tag
     * @param  mixed $left     left side value of the equality
     * @param  mixed $right    right side value of the equality
     * @param  string $operator comparison operator of the equality
     * @return bool           whether the equality is true
     */
    private function parseCondition($left, $right, $operator = self::OPERATOR_EQUALS): bool
    {
        switch($operator)
        {
            default:
            case self::OPERATOR_EQUALS:
                return $left == $right;
            case self::OPERATOR_NOT_EQUALS:
                return $left != $right;
            case self::OPERATOR_GREATER_THAN:
                return $left > $right;
            case self::OPERATOR_LESS_THAN:
                return $left < $right;
            case self::OPERATOR_GREATER_THAN_EQUALS:
                return $left >= $right;
            case self::OPERATOR_LESS_THAN_EQUALS:
                return $left <= $right;
        }
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
				$this->template = str_replace( $this->config('delimiter_start') . $var . $this->config('delimiter_end'), $content, $this->template, $repetitions );
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
			$this->cached = true;
			$this->load ( $file );
		}
		else
		{
			$copy = new Disk( array('name'=>$file) );
			$copy->contents($this->template);
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
			return $this->cached;
		$this->cached = ($value == true);
	}

	private function extractPlaceholders()
    {
        // Extract placeholders
        if(preg_match_all("/\{=(.*?)\}/", $this->template, $matches)) {
            foreach($matches[1] as $placeholder){
                // Extract placeholder options
                preg_match("/([^[\s]+)(?:\[(.*?)\])?/", $placeholder, $optionMatches);
                if (!empty($optionMatches)) {
                    $this->placeholderNames[] = $optionMatches[1];
                    $this->placeholders[] = $placeholder;
                }
            }
        }
    }

    private function handleVariables()
    {
        if(preg_match_all("/\{#var (.*?) = (.*?)\}/", $this->template, $matches)) {
            $vars = array_combine($matches[1], $matches[2]);
            $this->template = preg_replace("/\{#var (.*?) = (.*?)\}/", "", $this->template);
        }

        $this->assign($vars);

        // Process variables, conditions, and loops directly within the run method
        foreach($this->data as $var => $value) {
            // Handle array variables
            if (Arr::is($value)) {
                $value = implode(", ", $value);
            }

            // Also replace variables in conditions and loops
            foreach($this->conditions as &$condition) {
                $condition['condition'] = str_replace($var, $value, $condition['condition']);
                $condition['value'] = str_replace($var, $value, $condition['value']);
                $condition['content'] = str_replace($var, $value, $condition['content']);
            }
        }
    }

    private function handleConditions()
    {
        if(preg_match_all("/\{#if\((.*?)([!=<>]+)(.*?)\)\}(.*?)\{#endif\}/s", $this->template, $matches)) {
            $this->conditions = array_map(function($condition, $operator, $value, $content) {
                return ['condition' => $condition, 'operator' => $operator, 'value' => $value, 'content' => $content];
            }, $matches[1], $matches[2], $matches[3], $matches[4]);
            $condition = $matches[1][0];
            $operator = $matches[2][0];
            $value = $matches[3][0];

            if (strpos($condition, "'") === false && strpos($condition, "\"") === false) {
                $condition = $this->data[$condition] ?? null;
            }

            if (strpos($value, "'") === false && strpos($value, "\"") === false) {
                $value = $this->data[$value] ?? null;
            }

            $condition = trim($condition, "'\"");
            $value = trim($value, "'\"");

            if ( !$this->parseCondition($condition, $value, $operator) ) {
                // $this->data[$placeholderName] = "";
                // $i++;
                // continue;
            }

        }


        foreach ($this->conditions as $condition) {
            $conditionValue = $this->data[$condition['condition']] ?? null;

            $conditionValue = trim($conditionValue, "'\"");
            $condition['value'] = trim($condition['value'], "'\"");
            if ( $this->parseCondition($conditionValue, $condition['value'], $condition['operator']) ) {
                $this->template = preg_replace("/\{#if\((.*?)([!=<>]+)(.*?)\)\}(.*?)\{#endif\}/s", $condition['content'], $this->template);
            } else {
                $this->template = preg_replace("/\{#if\((.*?)([!=<>]+)(.*?)\)\}(.*?)\{#endif\}/s", "", $this->template);
            }
        }
    }

	protected function handleLoops()
	{
        if(preg_match_all("/\{#each\s*((?:\[[^\]]*\]|[^}]+))\}(.*?)\{#endeach\}/s", $this->template, $matches)) {
        	$rules = $matches[1][0];
            $iteratedContent ??= $matches[2][0];
            $iteratedData = null;
            $iteratedMax = null;
            $loopGlue = null;
            $iterationAssignment = null;
            $vars = [];

            if (strpos($rules, "[") === 0) {
                $options = trim($rules, '[]');
                $options = $this->parseOptions($options);
            } else {
                $vars = explode('=', $rules);
                $var = trim($vars[0]);
                $value = trim($vars[1]);
            }

            if (isset($options)) {
                $iteratedMax = $options['iterations'];
                $loopGlue = $options['glue'];
                $iterationAssignment = $placeholderNames[$position];
            } elseif ($value !== "") {
                if (strpos($value, "'") === false && strpos($value, "\"") === false) {
                    $value = $data[$value] ?? $vars[$value] ?? null;
                } else {
                    $value = trim($value, "'\"");
                }

                $iteratedData = (strpos($value, '[') === 0) ? $value : $vars[$value];
                if (preg_match("/\[(.*?)\]/", $iteratedData, $matches2)) {
                    $value = explode(",", trim($matches2[1]));
                    $iteratedData = array_map(function($v) { return trim($v, " '\""); }, $value);
                }

                $iterationAssignment = $var;
            }

            // TODO: fix this!
            for ($i = 0; $i < $iteratedMax; $i++ ) {
	            $render = "";
	            if ( isset($iterationAssignment) && !isset($data[$iterationAssignment])) {
	                $render = $iteratedContent;
	                if (!empty($iteratedData)) {
	                    $render = str_replace('{@current}', $iteratedData[$index], $render);
	                }
	                $render = str_replace('{@index}', $index+1, $render);

	                $data[$iterationAssignment] = [];
	            }

	            // $chunk = preg_replace("/\{#each\s*((?:\[[^\]]*\]|[^}]+))\}(.*?)$/s", $render, $chunk);
	            $this->template = str_replace($iteratedContent, $render, $this->template);
	        }
        	
        	preg_replace("/\{#each\s*((?:\[[^\]]*\]|[^}]+))\}(.*?)\{#endeach\}/s", $matches[2][0], $thisTemplate);
		}
	}

	/**
	 * Method to commit the data and formatting to the template
	 *
	 * @param mixed $formatted The formatting to apply to the data, if any
	 */
	public function commit( $formatted = null ): IObj
	{
		$this->set( $this->data->val(), $formatted );

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
		$this->executeModules();
		$this->applyTemplate();
		$this->commit( $this->config('format') );
		ob_start();
		if ($this->config('eval'))
			eval ( ' ?> ' . $this->template . ' <?php ' );
		else
			echo $this->template;
			
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
	private function executeModules(): IObj
	{
		if ( $this->template == null ) {
			return $this;
		}

		$pattern = "/@".$this->config('module_token')."\('(.*)'\)/";

		preg_match_all( $pattern, $this->template, $matches );

		for ($i = 0; $i < count($matches[0]); $i++) {
			$match = $matches[0][$i];
			$file = $matches[1][$i];
			$template = new Template();
			$template->load( $this->config('module_directory').DIRECTORY_SEPARATOR.$file);
			$template->set( $this->data->val() );
			$content = $template->render();
			$this->template = str_replace($match, $content, $this->template);
		}

		return $this;
	}

	private function applyTemplate(): IObj
	{
		if ( $this->template == null ) {
			return $this;
		}

		// if a `@template('path-to-template.html')` tag is found, load the template
		// load that content as the `$template`
		if (preg_match("/@template\('(.*)'\)/", $this->template, $matches)) {
			$content = $this->template;
			$template = new Template($this->config());
			$template->load($matches[1]);
			$this->template = $template->render();

			// if any content is wrapped in a `@section('section-name')...@endsection` tag,
			// replace that content with the content of the section from the template wherever 
			// there's an @output('section-name') tag
			if (preg_match_all("/@section\('(.*)'\)(.*?)@endsection/s", $content, $sectionMatches)) {
				foreach ($sectionMatches[1] as $i => $sectionName) {
					$sectionContent = $sectionMatches[2][$i];
					$this->template = str_replace("@output('$sectionName')", $sectionContent, $this->template);
				}
			}
		}

		return $this;
	}
}