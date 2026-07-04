<?php

namespace BlueFission\Utils;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Data\FileSystem;

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
    private static $_instance;

    private $_paths;
    private $_config = ['default_extension' => 'php','default_path' => '', 'full_stop' => '.'];

    /**
     * Constructor for the class
     *
     * It sets the _paths property to an array containing the current directory
     */
    private function __construct()
    {
        $this->_paths = Arr::make([])
            ->push(realpath(dirname(__FILE__)))
            ->val();
    }

    /**
     * This function returns an instance of the class
     *
     * @return ClassImporter
     */
    public static function instance()
    {
        if (!Val::is(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class();
        }

        return self::$_instance;
    }

    /**
     * This function gets or sets the configuration
     *
     * @param mixed $config The configuration key to get or set
     * @param mixed $value The value to set the configuration key to
     *
     * @return mixed
     */
    public function config($config = null, $value = null)
    {
        if (!Val::is($config)) {
            return $this->_config;
        } elseif (Str::is($config)) {
            if (!Val::is($value)) {
                return Arr::hasKey($this->_config, $config) ? $this->_config[$config] : null;
            }
            if (Arr::hasKey($this->_config, $config)) {
                $this->_config[$config] = $value;
            }
        } elseif (Arr::is($config)) {
            foreach (Arr::make($config)->filter(fn ($value, $key) => Arr::hasKey($this->_config, $key)) as $key => $newValue) {
                $this->_config[$key] = $newValue;
            }
        }
    }

    /**
     * This function adds a path to the _paths property
     *
     * @param string $path The path to add
     *
     * @return void
     */
    public function addPath($path)
    {
        $path = Str::trim((string)$path);

        if ($path === '') {
            return;
        }

        $this->_paths = Arr::make($this->_paths)
            ->push($path)
            ->val();
    }

    /**
     * This function loads the class specified in the fullyQualifiedClass parameter
     *
     * @param string $fullyQualifiedClass The fully qualified name of the class to load
     *
     * @return bool
     */
    public function load($fullyQualifiedClass)
    {
        $classPath = $this->getClassDirectoryPath($fullyQualifiedClass);

        if ($classPath === false) {
            return false;
        }

        if (Arr::is($classPath)) {
            foreach ($classPath as $path) {
                require_once($path);
            }
        } else {
            require_once($classPath);
        }
    }

    /**
     * Helper method to get the directory path of a fully qualified class
     *
     * @param string $fullyQualifiedClass The fully qualified class name (e.g. 'BlueFission\Utils\Loader')
     *
     * @return string|array|false The path to the class file, an array of paths if a wildcard match is found, or false if the class could not be found
     */
    private function getClassDirectoryPath($fullyQualifiedClass)
    {
        $pathParts = Str::make($fullyQualifiedClass)->split($this->config('full_stop'));
        $numberOfPathParts = $pathParts->size();
        $isWildcardMatch = $pathParts->get($numberOfPathParts - 1) == "*";
        $filePath = $this->classFilePath($pathParts, $isWildcardMatch);

        // Check if wildcard match
        if ($isWildcardMatch) {
            $wildcardMatches = Arr::make([]);
            foreach ($this->_paths as $path) {
                $testPath = $path . DIRECTORY_SEPARATOR . $filePath;
                if (is_dir($testPath)) {
                    $directory = dir($testPath);
                    while (false !== ($entry = $directory->read())) {
                        if ($entry != "." && $entry != ".." &&
                            Str::rpos($entry, ".".$this->_config['default_extension']) !== false) {
                            $wildcardMatches->push($testPath . $entry);
                        }
                    }
                    $directory->close();
                }
            }
            return $wildcardMatches->val();
        }

        // Check for file in the paths
        foreach ($this->_paths as $path) {
            $testPath = $path . DIRECTORY_SEPARATOR . $filePath;
            if (FileSystem::fileExists($testPath)) {
                return $testPath;
            }
        }

        // File not found
        return false;
    }

    private function classFilePath(Arr $pathParts, bool $isWildcardMatch): string
    {
        $segments = $isWildcardMatch ? $pathParts->slice(0, -1) : $pathParts->copy();

        if (!$isWildcardMatch) {
            $lastIndex = $segments->size() - 1;
            $segments->set(
                $lastIndex,
                $segments->get($lastIndex) . "." . $this->config('default_extension')
            );
        }

        return $segments->join(DIRECTORY_SEPARATOR)->val() . ($isWildcardMatch ? DIRECTORY_SEPARATOR : '');
    }
}
