<?php
namespace BlueFission\Utils;
/**
 * Class to import all class files.
 * 
 * All classes should use the Loader class to import
 * its classes.
 * 
 * Thanks to Daryl Ducharme for originally speccing out this class  
 */
set_include_path( '../../' );
/*
spl_autoload_register( function ( $className ) {
	include( $className . ".php");
});
*/
class Loader
{
	private static $_instance;
	
	private $_paths;
	private $_config = array('default_extension'=>'php','default_path'=>'', 'full_stop'=>'.');
	
	private function __construct()
	{
		$this->_paths = array();
		$this->_paths[] = realpath( dirname( __FILE__ ) );
	}
	
	/**
	 * @return ClassImporter
	 */
	static function instance( )
	{
		if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
	}
	
	public function config( $config = null, $value = null )
	{
		if (!isset ($config))
			return $this->_config;
		elseif (is_string($config))
		{
			if (!isset ($value))
				return isset($this->_config[$config]) ? $this->_config[$config] : null;
			if (array_key_exists($config, $this->_config))
				$this->_config[$config] = $value; 
		}
		elseif (is_array($config))
		{
			foreach ($this->config as $a=>$b)
				$this->_config[$a] = $config[$a];
		}
	}

	
	public function addPath( $path )
	{
		$this->_paths[] = $path;
	}
	
	public function load( $fullyQualifiedClass )
	{
		$classPath = $this->getClassDirectoryPath( $fullyQualifiedClass );
		
		if( $classPath === false )
		{       
			return false;
		}

		if( is_array( $classPath ) )
		{
			foreach( $classPath as $path )
			{
				require_once( $path );     
			}
		}
		else
		{
			require_once( $classPath );
		}
	}
	
	private function getClassDirectoryPath( $fullyQualifiedClass )
	{
		$pathParts = explode( ".", $fullyQualifiedClass );
		$numberOfPathParts = count( $pathParts );
		$filePath = "";
		$isWildcardMatch = $pathParts[ $numberOfPathParts - 1 ] == "*";
		for( $index = 0; $index < $numberOfPathParts; $index++ )
		{
			if( $index < $numberOfPathParts - 1 ) {
				$filePath .= $pathParts[$index] . DIRECTORY_SEPARATOR;
			} elseif( !$isWildcardMatch ) {
				$filePath .=  $pathParts[$index] . "." . $this->config('default_extension');
			}
		}
		
		if( $isWildcardMatch )
		{
			$wildcardMatches = array();
			foreach( $this->_paths as $path )
			{
				$testPath = $path . DIRECTORY_SEPARATOR . $filePath;
				if( is_dir( $testPath ) )
				{
					$directory = dir( $testPath );
					while(false !== ( $entry = $directory->read() ) )
					{
						if( $entry != "." && $entry != ".." && 
							strrpos( $entry, ".".$this->_config('default_extension') ) !== false )
						{
							$wildcardMatches[] = $testPath . $entry;
						}
					}
					$directory->close();
				}
			}
			return $wildcardMatches;
		}
		else
		{
			foreach( $this->_paths as $path )
			{
				$testPath = $path . "/" . $filePath;   
				if( is_file( $testPath ) )
					return $testPath;
			}			
		}
		
		return false;
	}
}