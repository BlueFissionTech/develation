<?php
namespace BlueFission\Data;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\HTML\HTML;
class FileSystem extends Data implements IData {
	private $_handle;
	private $_contents;
	
	protected $_config = array( 'mode'=>'r', 'filter'=>'/\..$|\.htm$|\.html$|\.pl$|\.txt$/i', 'home'=>'/' );

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
				$this->dir( $this->config('home') );
			}
			elseif ( is_string($data))
				$this->getInfo($data);
		} 
	}

	public function open( $file = null ) {
		if ( $file ) {
			$this->getInfo( $file );
		}
			
		$success = false;
		$path = $this->path();
		$file = $this->file();
		$status = "File opened successfully";
		
		if ($file != '') {
			if (!$this->exists($path)) $status = "File '$file' does not exist. Creating.\n";
			
			if (!$this->_handle = fopen($path, $this->config('mode'))) {
				$status = "Cannot access file ($file)\n";
			}
		} else {
			$status = "No file specified for opening\n";
		}
		
		$this->status($status);
		return $success;
	}

	public function close() {
		fclose ( $this->_handle );
		$this->_handle = null;
	}

	private function getInfo( $path ) {
		$info = pathinfo($path);
		if (is_array($info))
			$this->assign($info);
	}

	private function file() {
		if ( !$this->basename )
			$this->basename = implode( '.', array($this->filename, $this->extension) );
		return $this->basename;
	}

	private function path() {
		$path = implode( DIRECTORY_SEPARATOR, array($this->dirname, $this->file()) );
		$realpath = realpath($path);
		return $realpath ? $realpath : $path;
	}
	
	public function read( $file = null ) {
		$file = (DevValue::isNotNull($file)) ? $file : $this->path();
		
		if ( $this->exists($file) )
		{
			$this->contents(file_get_contents($file));
			return true;
		}
		elseif ( $this->_handle )
		{
			$this->content( fread( $handle ) );
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
		$content = stripslashes($this->contents());
		$status = '';
		if ($file != '') {
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
		$content = stripslashes($this->contents());
		$status = '';
		if ($file != '') {
			if (!$this->exists($path)) {
				$status = "File '$file' does not exist.\n";
			}
			elseif (is_writable($path)) {
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

	public function delete() {
		$status = false;
		$path = $this->path();
		$file = $this->file();
		
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
	
	public function exists($path) {
		$file = DevValue::isNotNull($path) ? basename($path) : $this->file();
		$directory = dirname($path) ? realpath( dirname($path) ) : $this->path();
		
		$path = realpath( join(DIRECTORY_SEPARATOR, array($directory, $file) ) );
		
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

	public function filter($type) {
		if ( DevValue::isNull($type) )
			return $this->config('filter');
			
		$pattern = '';
		if ( is_array($type) ) {
			$pattern = "/\\" . implode('$|\\', $type) . "$/i";
			$type = 'custom';
		}
		
		switch ($type) {
		case 'image':
			$extensions = "/\.gif$|\.jpeg$|\.tif$|\.jpg$|\.tif$|\.png$|\.bmp$/i";
		  	break;
	  	case 'document':
	  		$extensions = "/\.pdf$|\.doc$|\.txt$/i";
	  		break;
	  	default:
	  	case 'file':
	  		$extensions = '//';
	  		break;
		case 'web':
			$extensions = "/\..$|\.htm$|\.html$|\.pl$|\.txt$/i";
			break;
		case 'custom':
			$extensions = $pattern;
			break;
		}
		
		$this->config('filter', $extension);
	}
	
	public function listDir($table = false, $href = null, $query_r = null) {
		$output = '';
		$href = HTML::href($href, false);
		$extensions = $this->filter();
		$dir = $this->dirname;
		
		$files = scandir($dir);
		
		sort($files);
	 
		if ($show_table) $output = HTML::table($files, '', $href, $query_r, '#c0c0c0', '', 1, 1, $dir, $dir);
		else $output = $files;
	
		return $output;	
	}
	
	public function contents($data = null) {
		if (DevValue::isNull($data)) return $this->_contents;
		
		$this->_contents = $data;
	}
	
	public function copy( $dest, $original = null, $remove_orig = false ) {
		$status = false;
		$file = DevValue::isNotNull($original) ? $original : $this->path(); 
		if ($file != '') {
			if ($dest != '' && is_dir($dest)) {
				if ($this->exists($file)) {
					if (!$this->exists( $dest ) || $overwrite) {
						//copy process here
						if ($success) {
							$status = "Successfully copied file\n";
							if ($remove_orig) {
								$this->delete($file);
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
}