<?php
namespace BlueFission\Data;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\HTML\HTML;
use BlueFission\Net\HTTP;

class FileSystem extends Data implements IData {
	/**
	 * The file handle for the file being processed
	 *
	 * @var resource $_handle
	 */
	private $_handle;

	/**
	 * The contents of the file being processed
	 *
	 * @var string $_contents
	 */
	private $_contents;

	/**
	 * Indicates whether the file is locked or not
	 *
	 * @var bool $_isLocked
	 */
	private $_isLocked = false;

	/**
	 * The configuration options for the FileSystem class
	 *
	 * @var array $_config
	 */
	protected $_config = array( 
		'mode'=>'r', 
		'filter'=>['..','.htm','.html','.pl','.txt'], 
		'root'=>'', 
		'doNotConfirm'=>'false', 
		'lock'=>false 
	);

	/**
	 * The data stored for the file being processed
	 *
	 * @var array $_data
	 */
	protected $_data = array(
		'filename'=>'',
		'basename'=>'',
		'extension'=>'',
		'dirname'=>'',
	);
	
	/**
	 * Constructor for the FileSystem class
	 *
	 * @param mixed $config 
	 */
	public function __construct( $config = null ) {	
		parent::__construct();
		
		// Set the root directory to the current working directory
		$this->config('root', $this->config('root') ?? $this->getSystemRoot());
		
		if ( Val::isNotNull($config) ) {
			if ( Arr::isAssoc($config) )
			{
				$this->config($config);
			} elseif ( Str::is($config) ) {
				$this->loadInfo($config);
			}
		} 
	}

	/**
	 * Returns the lock status of the file being processed
	 *
	 * @return bool
	 */
	public function isLocked(): bool
	{
		return $this->_isLocked;
	}

	/**
	 * Opens a file and sets up the file handle and lock status
	 *
	 * @param string $file
	 * @return IObj
	 */
	public function open( $file = null ): IObj
	{
		if ( $file ) {
			$this->loadInfo( $file );
		}
			
		$success = false;
		$path = $this->path();
		$file = $this->file();
		$status = "File opened successfully";

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return $this;
		}

		$this->close();

		$filepath = $path.DIRECTORY_SEPARATOR.$file;

		if ($file) {
			if (!$this->exists($filepath)) $status = "File '$file' does not exist. Creating.";
			
			if (!$handle = @fopen($filepath, $this->config('mode'))) {
				$status = "Cannot access file ($filepath)";
			} else {
				if ($this->config('lock') && flock($handle, LOCK_EX)) {
					$this->_isLocked = true;
					$success = 'true';
					$this->_handle = $handle;
				} elseif (!$this->config('lock')) {
					$this->_handle = $handle;
					$this->close();
					$success = 'true';
				} else {
					$this->_isLocked = false;
					$status = "Couldn't acquire lock on file {$filepath}.";
				}
			}
		} else {
			$status = "No file specified for opening";
		}
		
		$this->status($status);
		
		return $this;
	}

	/**
	 * Close file handle
	 */
	public function close(): IObj
	{
		if ($this->_handle) {
			fclose ( $this->_handle );
		}
		$this->_handle = null;
		$this->_isLocked = false;

		return $this;
	}

	// Determines the root directory of the system whether it is Windows or Unix
	private function getSystemRoot(): string
	{
		$root = '/';
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$root = '';
			// We check if there's an existing drive letter at the beginning of $this->_info['dirname'] and use that if so
			if (preg_match('/^[A-Z]:/', $this->_info['dirname'], $matches)) {
				$root = $matches[0];
			} else {
				// If not, we check the __DIR__ constant
				$root = substr(__DIR__, 0, 3);
			}

			if ($root == '') {
				// If we still don't have a root, we check the drives on the system and use the first one that exists
				// (this is the most reliable way to get the root on Windows, since __DIR__ might not be set correctly in some cases)
				$drives = range('A', 'Z');
				foreach ($drives as $drive) {
					if (is_dir($drive.':\\')) {
						$root = $drive.':\\';
						break;
					}
				}
			}

		}
		return $root;
	}

	/**
	 * Get information about the path
	 * @param string $path
	 */
	private function loadInfo( $path ): IObj
	{
		$info = pathinfo($path);

		$this->config('root', $this->config('root') ?? $this->getSystemRoot());

		if (Arr::is($info)) {
			$dir = $info['dirname'] ?? '';
			
			if ($this->allowedDir($dir)) {
				$info['dirname'] = substr($dir, Str::len($this->config('root')), Str::len($dir) );
			}
			
			$this->assign($info);
		}

		return $this;
	}

	/**
	 * Get the file name
	 * @return string|null
	 */
	private function file(): string|null
	{
		if ( !$this->basename && $this->extension ) {
			$this->basename = implode( '.', [$this->filename, $this->extension] );
		} elseif ( !$this->basename ) {
			$this->basename = $this->filename;
		}

		return $this->basename;
	}

	/**
	 * Get the full path of the file
	 * @return string|null
	 */
	private function path(): string|null
	{
		$path = implode( DIRECTORY_SEPARATOR, [$this->config('root'), $this->dirname] ) ?? $this->getcwd();

		$realpath = realpath($path);
		return $realpath ? $realpath : $path;
	}

	/**
	 * Read a file
	 * @param string $file
	 * @return IObj
	 */
	public function read( $file = null ): IObj
	{
		$file = (Val::isNotNull($file)) ? $file : $this->file();
		$path = $this->path();
		$filepath = $path.DIRECTORY_SEPARATOR.$file;

		if (!$file) {
			$this->status("No file specified");
			return $this;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return $this;
		}
		
		if ( $this->exists($filepath) && !$this->config('lock'))
		{
			$this->contents(file_get_contents($filepath));
			return $this;
		}
		elseif ( $this->_handle )
		{
			$this->contents( fread( $this->_handle, filesize($filepath) ) );
			if ( $this->contents() === false )
			{
				$this->status( "File $file could not be read" );
				return $this;
			}
			else return $this;
		}
		else	
		{
			$this->status( "No such file. File does not exist" );
			return $this;
		}
	}

	/**
	 * Write contents to file
	 * @return IObj
	 */
	public function write(): IObj
	{
		$path = $this->path();
		$file = $this->file();

		if (!$this->file()) {
			$this->status("No file specified");
			return $this;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return $this;
		}

		$finfo = finfo_open(FILEINFO_MIME);

		$filepath = $path.DIRECTORY_SEPARATOR.$file;

		$content = $this->contents();

		$content = ( substr(finfo_file($finfo, $filepath), 0, 4) == 'text') ? stripslashes($content) : $content;

		$status = '';
		if ($file && !$this->config('lock')) {
			if (is_writable($path)) {
				if ( Val::isEmpty($content) ) {
					if (!$this->exists($filepath) && touch($filepath) ) {
						$status = "File '$file' has been created";
					} else {
						$status = "File '$file' already exists";
					}
				} elseif ( !file_put_contents($filepath, $content) ) {
					$status = "Cannot write to file ($file)";
				} else {	
					$status = "Successfully wrote to file '$file'";
				}
			} else {
				$status = "The file '$file' is not writable";
			}
		} elseif ($this->_handle) {
			if ( fwrite($this->_handle, $content) !== false) 
			{
				$status = "Successfully wrote to file '$file'";
			}
			else
			{
				$status = "Failed to write to file '$file'";
			}
		} else {
			$status = "No file specified for edit";
		}
		
		$this->status($status);

		return $this;
	}
	
	/**
	 * Flushes the contents of a file.
	 * 
	 * @return IObj
	 */
	public function flush() {
		$path = $this->path();
		$file = $this->file();

		if (!$this->file()) {
			$this->status("No file specified");
			return $this;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return $this;
		}

		$filepath = $path.DIRECTORY_SEPARATOR.$file;

		$content = (!empty($this->contents()) && Str::is($this->contents()) ) ? stripslashes($this->contents()) : $this->contents();
		$status = '';
		if ($file != '') {
			if (!$this->exists($filepath)) {
				$status = "File '$file' does not exist.";
			}
			elseif (is_writable($filepath) && !$this->config('lock')) {
				if (!file_put_contents($filepath, "") ) {
					$status = "Cannot empty file ($file)";
					//exit;
				} else {	
					$status = "Successfully emptied '$file'";
				}
			} else {
				$status = "The file '$file' is not writable";
			}
		} elseif ($this->_handle) {
			if ( ftruncate($this->_handle) !== false) {
				$status = "Successfully emptied '$file'";
			} else {
				$status = "Failed to empty file '$file'";
			}
		} else {
			$status = "No file specified for edit";
		}
		
		$this->status($status);	

		return $this;
	}

	/**
	 * Deletes a file.
	 * 
	 * @param boolean $confirm Confirm deletion
	 * 
	 * @return IObj
	 */
	public function delete( $confirm = null ): IObj
	{
		$status = false;
		$path = $this->path();
		$file = $this->file();

		if (!$this->file()) {
			$this->status("No file specified");
			return $this;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return $this;
		}

		$filepath = $path.DIRECTORY_SEPARATOR.$file;

		$confirm = Val::isNotNull($confirm) ? $confirm : $this->config('doNotConfirm');
		
		if ($filepath) {
			if ($confirm === true) {
				if ($this->exists($filepath)) {
					if (is_writable($filepath)) {
						if (unlink($filepath) === false) {
							$status = "Cannot delete file ($file)";
						} else {
							$status = "Successfully deleted file '$file'";
						}	
					} else {
						$status = "The file '$file' is not editable";
					}
				} else {
					$status = "File '$file' does not exist";
				}
			} else {
				$status = "Must confirm action before file deletion";		
			}
		} else {
			$status = "No file specified for deletion";
		}
		
		$this->status($status);

		return $this;
	}
	/**
	 * Check if the file exists at the given path
	 * 
	 * @param string|null $path The path to the file, if null, the file name is obtained from the $this->file() function
	 * @return bool True if file exists, false otherwise
	 */
	public function exists($path = null): bool
	{
		$file = Val::isNotNull($path) ? basename($path) : $this->file();
		$directory = dirname($path) ? realpath( dirname($path) ) : $this->path();

		
		$path = realpath( join(DIRECTORY_SEPARATOR, array($directory, $file) ) );
		
		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		return file_exists($path);
	}

	/**
	 * Upload a file
	 * 
	 * @param array $document The file to be uploaded
	 * @param bool $overwrite Whether to overwrite the file if it already exists
	 * @return IObj
	 */
	public function upload( $document, $overwrite = false ): IObj
	{
		$status = '';
			
		if ($document['name'] != '') {
			
			$extensions = $this->filter();
			
			if (preg_match($extensions, $document['name'])) {
				$location = $this->dirname .'/'. (($this->filename == '') ? basename($document['name']) : $this->file());
				if ($document['size'] > 1) {
					if (is_uploaded_file($document['tmp_name'])) {

						if (!$this->allowedDir($location)) {
							$this->status( "Location is outside of allowed path.");
							return $this;
						}

						if (!$this->exists( $location ) || $overwrite) {
							if (move_uploaded_file( $document['tmp_name'], $location )) {
								$status = 'Upload Completed Successfully';
							} else {
								$status = 'Transfer aborted for file ' . basename($document['name']) . '. Could not copy file';
							}
						} else {
							$status = 'Transfer aborted for file ' . basename($document['name']) . '. Cannot be overwritten'; 
						}
					} else {
						$status = 'Transfer aborted for file ' . basename($document['name']) . '. Not a valid file';
					}
				} else {
					$status = 'Upload of file ' . basename($document['name']) . ' Unsuccessful';
				}
			} else {
				$status = 'File "' . basename($document['name']) . '" is not an appropriate file type. Expecting '.$type.'. Upload failed.';
			}
		}
		
		$this->status($status);

		return $this;
	}

	/**
	 * Filter the files based on the specified type
	 * 
	 * @param mixed $type (null, 'image', 'document', 'file', 'web', or an array)
	 * @return IObj|string|array
	 */
	public function filter($type = null): Obj|string|array
	{
		if ( Val::isNull($type) ) {
			return $this->config('filter');
		}
		
		if ( is_array($type) ) {
			$array = $type;
			$type = 'custom';
		}
		
		switch ($type) {
		case 'image':
			$extensions = array('.gif','.jpeg','.tiff','.jpg','.tif','.png','.bmp');
		  	break;
		case 'document':
			$extensions = array('.pdf','.doc','.docx','.txt');
		  	break;
		default:
		case 'file':
			$extensions = array();
			break;
		case 'web':
			$extensions = array('.htm','.html','.pl','.txt');
			break;
		case 'custom':
			$extensions = $array;
			break;
		}
		
		$this->config('filter', $extensions);

		return $this;
	}

	/**
	 * Get the regular expression pattern for the filter type
	 * 
	 * @return string (regex pattern)
	 */
	private function filter_regex (): string
	{
		$type = $this->config('filter');
		$pattern = '';
		if ( Arr::is($type) ) {
			$pattern = "/\\" . implode('$|\\', $type) . "$/i";
		}
		return $pattern;
	}

	/**
	 * List the directory files
	 * 
	 * @param boolean $table (if true, return the files in a table format)
	 * @param mixed $href (base URL for the files)
	 * @param mixed $query_r (query string for the URL)
	 * @return mixed (string or array of file names)
	 */
	public function listDir($table = false, $href = null, $query_r = null) {
		$output = '';
		$href = HTML::href($href, false);
		$extensions = $this->filter();
		$dir = $this->path();

		if (!$this->allowedDir($dir)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		$filters = $this->config('filter');

		$filter = (Val::isNotNull($filters) && Arr::size($filters) > 0) 
			? '{'.implode(',*', $filters ?? []).'}' : '*';

		$files = glob($this->path().DIRECTORY_SEPARATOR.$filter, GLOB_BRACE);
		
		foreach ($files as $key=>$file) {
			$files[$key] = basename($file);
		}

		// $files = scandir($this->path());
		
		// sort($files);
	 
		if ($table) {
			$output = HTML::table($files, '', $href, $query_r, '#c0c0c0', '', 1, 1, $dir, $dir);
		} else {
			$output = $files;
		}

		$this->status('Success');

		return $output;	
	}
	
	/**
	 * Get or set the contents of the file
	 *
	 * @param string|null $data The data to set as the contents of the file
	 *
	 * @return mixed The contents of the file
	 */
	public function contents($data = null): mixed
	{
		if (Val::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;

		return null;
	}

	/**
	 * Copy a file to a specified destination
	 *
	 * @param string      $dest      The destination to copy the file to
	 * @param string|null $original  The path to the original file, if not the file property of the object
	 * @param bool        $remove_orig If true, the original file will be removed after the copy is complete
	 *
	 * @return string A status message indicating the result of the copy
	 */
	public function copy( $dest, $original = null, $remove_orig = false ): IObj
	{
		$status = false;

		if (!$original && !$this->file) {
			$this->status("No file specified");
			return $this;
		}

		$file = Val::isNotNull($original) ? $original : $this->path(); 

		if (!$this->allowedDir($file)) {
			$this->status( "Location is outside of allowed path.");
			return $this;
		}

		if (!$this->allowedDir($dest)) {
			$this->status( "Destination is outside of allowed path.");
			return $this;
		}

		if ($file != '') {
			if ($dest != '' && is_dir($dest)) {
				if ($this->exists($file)) {
					if (!$this->exists( $dest ) || $overwrite) {
						//copy process here
						if ($success) {
							$status = "Successfully copied file";
							if ($remove_orig) {
								$this->delete($file);
								if (!$this->exists($this->file()))
									$this->dirname = $dest;
							}
						} else {
							$status = "Copy failed: file could not be moved";
						}
					} else {
						$status = "Copy aborted. File cannot be overwritten";
					}
				} else {
					$status = "File '$file' does not exist";
				}
			} else {
				$status = "No file destination specified or destination does not exist";
			}
		} else {
			$status = "No file specified for deletion";
		}
		
		$this->status($status);

		return $this;
	}

	/**
	 * move function
	 *
	 * This function moves a file or directory to the specified destination.
	 *
	 * @param string $dest    The destination to move the file or directory to.
	 * @param string $original The original file or directory to be moved. If not provided, the current file or directory will be used.
	 *
	 * @return IObj
	 */
	public function move( $dest, $original = null): IObj
	{
		$this->copy( $dest, $original, true );

		return $this;
	}

	/**
	 * allowedDir function
	 *
	 * This function checks if the directory is within the allowed base directory.
	 *
	 * @param string $path The path of the directory to be checked.
	 *
	 * @return bool Returns true if the directory is within the allowed base directory, and false otherwise.
	 */
	private function allowedDir( $path )
	{
		// TODO: Check against basedir restrictions
		// $basedir = ini_get('open_basedir');
		// if ( $basedir ) {
		// 	$directories = exploode(PATH_SEPARATOR, $basedir);
		// 	if (is_array($directories)) {
		// 		foreach ($directories as $directory) {
		// 			if ( $root == $directory ){

		// 			} else {
		// 				$directory = realpath($directory);
		// 				$position = strpos ( $dir, $root );
		// 			}
		// 		}
		// 	}
		// }

		$root = @realpath ( $this->config('root') );
		$dir = @realpath($path);


		if ( !$root ) {
			// return false;
			$root = @realpath($this->dirname) ? @realpath($this->dirname) : ( $this->dirname ? $this->dirname : DIRECTORY_SEPARATOR );
		}

		if ( !$dir ) {
			$dir = str_replace(array('..'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.'..'), array('',''), $path);
		}

		$len1 = strlen($root);
		$len2 = strlen($dir);

		$position = ( $len2 > 0 ) ? strpos ( $dir, $root ) : 0;

		$allowed = ( $len1 <= $len2 && $position === 0 ) ? true : false;

		return $allowed;
	}

	/**
	 * __destruct function
	 *
	 * This function is automatically called when the object is destroyed. It closes any open connections.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->close();
		parent::__destruct();
	}

	/**
	 * Create a new directory.
	 *
	 * @param string|null $dir The name of the directory to be created.
	 * @return IObj
	 */
	public function mkdir( $dir = null ): IObj
	{
	    $dir = $dir ? $this->path().DIRECTORY_SEPARATOR.$dir : $this->path();

	    if (!$this->allowedDir($dir)) {
	        $this->status( "Location is outside of allowed path.");
	        return $this;
	    }

	    if (!$this->exists($dir)) {
	        mkdir($dir);
	    }

	    return $this;
	}
}