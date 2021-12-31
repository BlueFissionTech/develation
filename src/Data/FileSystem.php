<?php
namespace BlueFission\Data;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\HTML\HTML;
class FileSystem extends Data implements IData {
	private $_handle;
	private $_contents;
	private $_is_locked = false;

	protected $_config = array( 'mode'=>'r', 'filter'=>array('..','.htm','.html','.pl','.txt'), 'root'=>'/', 'doNotConfirm'=>'false', 'lock'=>false );

	protected $_data = array(
		'filename'=>'',
		'basename'=>'',
		'extension'=>'',
		'dirname'=>'',
		);
	
	public function __construct( $data = null ) {	
		parent::__construct();
		if (DevValue::isNotNull($data)) {
			if ( DevArray::isAssoc($data) )
			{
				$this->config($data);
				// $this->dirname = $this->config('root') ? $this->config('root') : getcwd();
			}
			elseif ( is_string($data))
				$this->getInfo($data);
		} 
	}

	public function isLocked() {
		return $this->_is_locked;
	}

	public function open( $file = null ) {
		if ( $file ) {
			$this->getInfo( $file );
		}
			
		$success = false;
		$path = $this->path();
		$file = $this->file();
		$status = "File opened successfully";

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		$this->close();

		if ($file != '') {
			if (!$this->exists($path)) $status = "File '$file' does not exist. Creating.\n";
			
			if (!$handle = @fopen($path, $this->config('mode'))) {
				$status = "Cannot access file ($path)\n";
			} else {
				if ($this->config('lock') && flock($handle, LOCK_EX)) {
					$this->_is_locked = true;
					$success = 'true';
					$this->_handle = $handle;
				} elseif (!$this->config('lock')) {
					$this->_handle = $handle;
					$success = 'true';
				} else {
					$this->_is_locked = false;
					$status = "Couldn't acquire lock on file {$lock}.";
				}
			}
		} else {
			$status = "No file specified for opening\n";
		}
		
		$this->status($status);
		return $success;
	}

	public function close() {
		if ($this->_handle)
			fclose ( $this->_handle );
		$this->_handle = null;
		$this->_is_locked = false;
	}

	private function getInfo( $path ) {
		$info = pathinfo($path);
		if (is_array($info)) {
			$dir = $info['dirname'] ?? '';
			
			if ($this->allowedDir($dir)) {
				$info['dirname'] = substr($dir, strlen($this->config('root')), strlen($dir) );
			}
			
			$this->assign($info);
		}
	}

	private function file() {
		if ( !$this->basename && $this->extension )
			$this->basename = implode( '.', array($this->filename, $this->extension) );
		elseif ( !$this->basename )
			$this->basename = $this->filename;

		return $this->basename;
	}

	private function path() {
		if ($this->file())
			$path = implode( DIRECTORY_SEPARATOR, array($this->config('root'), $this->dirname, $this->file()) );
		else
			$path = implode( DIRECTORY_SEPARATOR, array($this->config('root'), $this->dirname) );

		$realpath = realpath($path);
		return $realpath ? $realpath : $path;
	}
	
	public function read( $file = null ) {
		$file = (DevValue::isNotNull($file)) ? $file : $this->path();

		if (!$this->file()) {
			$this->status("No file specified");
			return false;
		}

		if (!$this->allowedDir($file)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}
		
		if ( $this->exists($file) && !$this->config('lock'))
		{
			$this->contents(file_get_contents($file));
			return true;
		}
		elseif ( $this->_handle )
		{
			$this->contents( fread( $this->_handle, filesize($file) ) );
			if ( $this->contents() === false )
			{
				$this->status( "File $file could not be read" );
				return false;
			}
			else return true;
		}
		else	
		{
			$this->status( "No such file. File does not exist\n" );
			return false;
		}
	}
	
	public function write() {
		$path = $this->path();
		$file = $this->file();

		if (!$this->file()) {
			$this->status("No file specified");
			return false;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		$finfo = finfo_open(FILEINFO_MIME);

		$content = ( substr(finfo_file($finfo, $path), 0, 4) == 'text') ? stripslashes($this->contents()) : $this->contents();
		$status = '';
		if ($file != '' && !$this->config('lock')) {
			if (!$this->exists($path)) $status = "File '$file' does not exist. Creating.\n";
			if (is_writable($path)) {
				if (!file_put_contents($path, $content) )
				{
					$status = "Cannot write to file ($file)\n";
					//exit;
				} else {	
					$status = "Successfully wrote to file '$file'\n";
				}
			} else {
				$status = "The file '$file' is not writable\n";
			}
		} elseif ($this->_handle) {
			if ( fwrite($this->_handle, $content) !== false) 
			{
				$status = "Successfully wrote to file '$file'\n";
			}
			else
			{
				$status = "Failed to write to file '$file'\n";
			}
		} else {
			$status = "No file specified for edit\n";
		}
		
		$this->status($status);
	}
	
	public function flush() {
		$path = $this->path();
		$file = $this->file();

		if (!$this->file()) {
			$this->status("No file specified");
			return false;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		$content = stripslashes($this->contents());
		$status = '';
		if ($file != '') {
			if (!$this->exists($path)) {
				$status = "File '$file' does not exist.\n";
			}
			elseif (is_writable($path) && !$this->config('lock')) {
				if (!file_put_contents($path, "") ) {
					$status = "Cannot empty file ($file)\n";
					//exit;
				} else {	
					$status = "Successfully emptied '$file'\n";
				}
			} else {
				$status = "The file '$file' is not writable\n";
			}
		} elseif ($this->_handle) {
			if ( ftruncate($this->_handle) !== false) {
				$status = "Successfully emptied '$file'\n";
			} else {
				$status = "Failed to empty file '$file'\n";
			}
		} else {
			$status = "No file specified for edit\n";
		}
		
		$this->status($status);	
	}

	public function delete( $confirm = null ) {
		$status = false;
		$path = $this->path();
		$file = $this->file();

		if (!$this->file()) {
			$this->status("No file specified");
			return false;
		}

		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		$confirm = DevValue::isNotNull($confirm) ? $confirm : $this->config('doNotConfirm');
		
		if ($path != '') {
			if ($confirm === true) {
				if ($this->exists($path)) {
					if (is_writable($path)) {
						if (unlink($path) === false) {
							$status = "Cannot delete file ($file)\n";
						} else {
							$status = "Successfully deleted file '$file'\n";
						}	
					} else {
						$status = "The file '$file' is not editable\n";
					}
				} else {
					$status = "File '$file' does not exist\n";
				}
			} else {
				$status = "Must confirm action before file deletion\n";		
			}
		} else {
			$status = "No file specified for deletion\n";
		}
		
		$this->status($status);
	}
	
	public function exists($path = null) {
		$file = DevValue::isNotNull($path) ? basename($path) : $this->file();
		$directory = dirname($path) ? realpath( dirname($path) ) : $this->path();

		
		$path = realpath( join(DIRECTORY_SEPARATOR, array($directory, $file) ) );
		
		if (!$this->allowedDir($path)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		return file_exists($path);
	}
	
	public function upload( $document, $overwrite = false ) {
		$status = '';
			
		if ($document['name'] != '') {
			
			$extensions = $this->filter();
			
			if (preg_match($extensions, $document['name'])) {
				$location = $this->dirname .'/'. (($this->filename == '') ? basename($document['name']) : $this->file());
				if ($document['size'] > 1) {
					if (is_uploaded_file($document['tmp_name'])) {

						if (!$this->allowedDir($location)) {
							$this->status( "Location is outside of allowed path.");
							return false;
						}

						if (!$this->exists( $location ) || $overwrite) {
							if (move_uploaded_file( $document['tmp_name'], $location )) {
								$status = 'Upload Completed Successfully' . "\n";
							} else {
								$status = 'Transfer aborted for file ' . basename($document['name']) . '. Could not copy file' . "\n";
							}
						} else {
							$status = 'Transfer aborted for file ' . basename($document['name']) . '. Cannot be overwritten' . "\n"; 
						}
					} else {
						$status = 'Transfer aborted for file ' . basename($document['name']) . '. Not a valid file' . "\n";
					}
				} else {
					$status = 'Upload of file ' . basename($document['name']) . ' Unsuccessful' . "\n";
				}
			} else {
				$status = 'File "' . basename($document['name']) . '" is not an appropriate file type. Expecting '.$type.'. Upload failed.';
			}
		}
		
		$this->status($status);
	}

	public function filter($type = null) {
		if ( DevValue::isNull($type) )
			return $this->config('filter');
		
		if ( is_array($type) ) {
			$array = $type;
			$type = 'custom';
		}
		
		switch ($type) {
		case 'image':
			$extensions = array('.gif','.jpeg','.tiff','.jpg','.tif','.png','.bmp');
		  	break;
	  	case 'document':
	  		// $extensions = "/\.pdf$|\.doc$|\.txt$/i";
	  		$extensions = array('.pdf','.doc','.docx','.txt');
	  		break;
	  	default:
	  	case 'file':
	  		// $extensions = '//';
	  		$extensions = array();
	  		break;
		case 'web':
			// $extensions = "/\..$|\.htm$|\.html$|\.pl$|\.txt$/i";
			$extensions = array('.htm','.html','.pl','.txt');
			break;
		case 'custom':
			$extensions = $array;
			break;
		}
		
		$this->config('filter', $extensions);
	}

	private function filter_regex () {
		$type = $this->config('filter');
		$pattern = '';
		if ( is_array($type) ) {
			$pattern = "/\\" . implode('$|\\', $type) . "$/i";
		}
		return $pattern;
	}
	
	public function listDir($table = false, $href = null, $query_r = null) {
		$output = '';
		$href = HTML::href($href, false);
		$extensions = $this->filter();
		$dir = $this->path();

		if (!$this->allowedDir($dir)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		$filter = count($this->config('filters')) > 0 ? '/{'.implode(',*', $this->config('filters')).'}' : '/*';

		$files = glob($this->path().$filter, GLOB_BRACE);

		// $files = scandir($this->path());
		
		// sort($files);
	 
		if ($table) $output = HTML::table($files, '', $href, $query_r, '#c0c0c0', '', 1, 1, $dir, $dir);
		else $output = $files;

		$this->status('Success');

		return $output;	
	}
	
	public function contents($data = null) {
		if (DevValue::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;
	}
	
	public function copy( $dest, $original = null, $remove_orig = false ) {
		$status = false;

		if (!$original && !$this->file) {
			$this->status("No file specified");
			return false;
		}

		$file = DevValue::isNotNull($original) ? $original : $this->path(); 

		if (!$this->allowedDir($file)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		if (!$this->allowedDir($dest)) {
			$this->status( "Destination is outside of allowed path.");
			return false;
		}

		if ($file != '') {
			if ($dest != '' && is_dir($dest)) {
				if ($this->exists($file)) {
					if (!$this->exists( $dest ) || $overwrite) {
						//copy process here
						if ($success) {
							$status = "Successfully copied file\n";
							if ($remove_orig) {
								$this->delete($file);
								if (!$this->exists($this->file()))
									$this->dirname = $dest;
							}
						} else {
							$status = "Copy failed: file could not be moved\n";
						}
					} else {
						$status = "Copy aborted. File cannot be overwritten\n";
					}
				} else {
					$status = "File '$file' does not exist\n";
				}
			} else {
				$status = "No file destination specified or destination does not exist\n";
			}
		} else {
			$status = "No file specified for deletion\n";
		}
		
		$this->status($status);
	}

	public function move( $dest, $original = null) {
		$this->copy( $dest, $original, true );
	}

	private function allowedDir( $path ) {
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
		if (strpos($root, DIRECTORY_SEPARATOR) !== 0 ) {
			$root = DIRECTORY_SEPARATOR . $root;
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

	public function __destruct() {
		$this->close();
		parent::__destruct();
	}

	public function mkdir( $dir = null ) {
		$dir = $dir ? $this->path().DIRECTORY_SEPARATOR.$dir : $this->path();

		if (!$this->allowedDir($dir)) {
			$this->status( "Location is outside of allowed path.");
			return false;
		}

		if (!$this->exists($dir))
			mkdir($dir);
	}
}