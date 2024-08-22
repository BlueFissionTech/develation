<?php
namespace BlueFission\Utils;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;

/**
 * Class to import all class files.
 * 
 * All classes should use the Loader class to import
 * its classes.
 * 
 * Thanks to Daryl Ducharme for originally speccing out this class  
 */
class Loader
{
    private static $instance;

    private $paths;
    private $config = ['default_extension'=>'php','default_path'=>'', 'full_stop'=>'.'];

    /**
     * Constructor for the class
     *
     * It sets the paths property to an array containing the current directory
     */
    private function __construct()
    {
        $this->paths = [];
        $this->paths[] = realpath( dirname( __FILE__ ) );
    }

    /**
     * This function returns an instance of the class
     *
     * @return ClassImporter
     */
    static function instance( )
    {
        if (!Val::is(self::$instance)) {
            $class = __CLASS__;
            self::$instance = new $class;
        }

        return self::$instance;
    }

    /**
     * This function gets or sets the configuration
     *
     * @param mixed $config The configuration key to get or set
     * @param mixed $value The value to set the configuration key to
     *
     * @return mixed
     */
    public function config( $config = null, $value = null )
    {
        if (!Val::is ($config))
            return $this->config;
        elseif (Str::is($config))
        {
            if (!Val::is ($value))
                return Val::is($this->config[$config]) ? $this->config[$config] : null;
            if (Arr::hasKey($this->config, $config))
                $this->config[$config] = $value; 
        }
        elseif (Arr::is($config))
        {
            foreach ($this->config as $a=>$b)
                $this->config[$a] = $config[$a];
        }
    }

    /**
     * This function adds a path to the paths property
     *
     * @param string $path The path to add
     *
     * @return void
     */
    public function addPath( $path )
    {
        $this->paths[] = $path;
    }

    /**
     * This function loads the class specified in the fullyQualifiedClass parameter
     *
     * @param string $fullyQualifiedClass The fully qualified name of the class to load
     *
     * @return bool
     */
    public function load( $fullyQualifiedClass )
    {
        $classPath = $this->getClassDirectoryPath( $fullyQualifiedClass );

        if( $classPath === false )
        {       
            return false;
        }

        if( Arr::is( $classPath ) )
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
	
	/**
	 * Helper method to get the directory path of a fully qualified class
	 * 
	 * @param string $fullyQualifiedClass The fully qualified class name (e.g. 'BlueFission\Utils\Loader')
	 *
	 * @return string|array|false The path to the class file, an array of paths if a wildcard match is found, or false if the class could not be found
	 */
	private function getClassDirectoryPath( $fullyQualifiedClass )
	{
	    $pathParts = explode( ".", $fullyQualifiedClass );
	    $numberOfPathParts = Arr::size( $pathParts );
	    $filePath = "";
	    $isWildcardMatch = $pathParts[ $numberOfPathParts - 1 ] == "*";

	    // Build the file path
	    for( $index = 0; $index < $numberOfPathParts; $index++ )
	    {
	        if( $index < $numberOfPathParts - 1 ) {
	            $filePath .= $pathParts[$index] . DIRECTORY_SEPARATOR;
	        } elseif( !$isWildcardMatch ) {
	            $filePath .=  $pathParts[$index] . "." . $this->config('default_extension');
	        }
	    }

	    // Check if wildcard match
	    if( $isWildcardMatch )
	    {
	        $wildcardMatches = [];
	        foreach( $this->paths as $path )
	        {
	            $testPath = $path . DIRECTORY_SEPARATOR . $filePath;
	            if( is_dir( $testPath ) )
	            {
	                $directory = dir( $testPath );
	                while(false !== ( $entry = $directory->read() ) )
	                {
	                    if( $entry != "." && $entry != ".." && 
	                        Str::rpos( $entry, ".".$this->config['default_extension'] ) !== false )
	                    {
	                        $wildcardMatches[] = $testPath . $entry;
	                    }
	                }
	                $directory->close();
	            }
	        }
	        return $wildcardMatches;
	    }

	    // Check for file in the paths
	    foreach( $this->paths as $path )
	    {
	        $testPath = $path . DIRECTORY_SEPARATOR . $filePath;
	        if( file_exists( $testPath ) )
	        {
	            return $testPath;
	        }
	    }

	    // File not found
	    return false;
	}
}