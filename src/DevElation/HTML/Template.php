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


//dev_template
class Template extends Configurable {
	private $_template;
	private $_cached;
	private $_file;
	
	protected $_config = array(
		'file'=>'',
		'cache'=>true,
		'cache_expire'=>60,
		'cache_directory'=>'cache/',
		'max_records'=>1000, 
		'delimiter_start'=>'{', 
		'delimiter_end'=>'}',
		'module_token'=>'mod', 
		'module_directory'=>'modules/',
		'format'=>false,
		'eval'=>false,
	);
	
	public function __construct ( $config = null ) 
	{
		parent::__construct( $config );
		if ( DevValue::isNotNull( $config ) ) {
			if (DevArray::isAssoc($config))
			{
				$this->config($config);
				$this->load($this->config('file'));
			}
			else
				$this->load($config);
		}
		$this->_cached = false;

		$this->dispatch( State::DRAFT );
	}
	public function load ( $file = null ) 
	{
		if ( DevValue::isNotNull($file))
		{
			$this->_file = new FileSystem($file);
			$this->_file->open();
		}
		if ( $this->_file )
		{
			$this->_file->read();
			$this->_template = $this->_file->contents();
		}
	}
	
	public function contents($data = null)
	{
		if (DevValue::isNull($data)) return $this->_template;
		
		$this->_template = $data;
	}
	
	public function clear () 
	{
		parent::clear();
		$this->reset();
	}

	public function reset()
	{
		$this->load();
	}

	public function set( $var, $content = null, $formatted = null  ) 
	{
		if ($formatted)
			$content = HTML::format($content);

		if (is_string($var))
		{
			if ( !$content )
			{
				//throw new InvalidArgumentException( 'Cannot set empty value.');
				//return false;
			}

			if ( DevValue::isNotNull($formatted) && !is_bool($formatted) )
			{
				throw new InvalidArgumentException( 'Formatted argument expects boolean');
			}

			$this->_template = str_replace ( $this->config('delimiter_start') . $var . $this->config('delimiter_end'), $content, $this->_template );
		}
		elseif ( is_object( $var ) || DevArray::isAssoc( $var ) )
		{

			if ( $formatted == null )
				$formatted = $content;

			foreach ($var as $a=>$b) 
			{
				$this->set($a, $b, $formatted);
			}
		}
		else
		{
			throw new InvalidArgumentException( 'Invalid property' );
		}
	}

	//alias parent "field()"
	public function field( $var, $content = null, $formatted = null ) 
	{
		if ($formatted)
			$content = HTML::format($content);

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
	}

	public function assign( $data, $formatted = null )
	{
		$this->field($data, $formatted);
	}
	
	public function cache ( $minutes = null ) 
	{
		$file = $this->config('cache_directory').$_SERVER['REQUEST_URI'];
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
	
	private function cached ( $value ) 
	{
		if (DevValue::isNull($value))
			return $this->_cached;
		$this->_cached = ($value == true);
	}

	public function commit( $formatted = null )
	{
		$this->set( $this->_data, $formatted );
	}
	
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
	
	public function render ( ) 
	{
		//$this->executeModules();
		$this->commit( $this->config('format') );
		ob_start();
		if ($this->config('eval'))
			eval ( ' ?> ' . $this->_template . ' <?php ' );
		else
			echo $this->_template;
			
		return ob_get_clean();
	}

	public function publish ( ) 
	{
		print($this->render());
	}
}