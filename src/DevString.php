<?php
namespace BlueFission;

class DevString extends DevValue implements IDevValue {
	protected $_type = "string";

	public function _random($length = 8, $symbols = false) {
		$alphanum = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if ($symbols) $alphanum .= "~!@#\$%^&*()_+=";

		if ( $this->_data == "" ) {
			$this->_data = $alphanum;
		}
		$rand_string = '';
		for($i=0; $i<$length; $i++)
			$rand_string .= $this->_data[rand(0, strlen($this->_data)-1)];

		return $rand_string;
	}

	// https://www.uuidgenerator.net/dev-corner/php
	public function _uuid4()
	{
	    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
	    $data = $data ?? random_bytes(16);
	    assert(strlen($data) == 16);

	    // Set version to 0100
	    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	    // Set bits 6-7 to 10
	    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

	    // Output the 36 character UUID.
	    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	// truncate a string to a given number of words using space as a word boundary
	public function _truncate($limit = 40) {
		$string = trim( $this->_data );
		$string_r = explode(' ', $string, ($limit+1));
		if (count($string_r) >= $limit && $limit > 0) array_pop($string_r);
		$output = implode (' ', $string_r);
		return $output;
	}

	// test if two strings match
	public function _match($str2) {
		$str1 = $this->_data;
		return ($str1 == $str2);
	}

	// Encrypt a string
	public function _encrypt($mode = null) {
		$string = $this->_data;
		switch ($mode) {
		default:
		case 'md5':
			$string = md5($string);
			break;
		case 'sha1':
			$string = sha1($string);
			break;
		}
		
		return $output;
	}

	// Reverse strpos
	public function _strrpos($needle) {
		$haystack = $this->_data;
		$i = strlen($haystack);
		while ( substr( $haystack, $i, strlen( $needle ) ) != $needle ) 
		{
			$i--;
			if ( $i < 0 ) return false;
		}
		return $i;
	}

	// test is a string exists in another string
	public function _has($needle) {
		$haystack = $this->_data;
		return (strpos($haystack, $needle) !== false);
	}

	public function _similarityTo($string) {
		// via vasyl at vasyltech dot com from https://secure.php.net/manual/en/function.similar-text.php
		$string1 = $this->_data;
		$string2 = $string;

	    $len1 = strlen($string1);
	    $len2 = strlen($string2);
	    
	    $max = max($len1, $len2);
	    $similarity = $i = $j = 0;
	    
	    while (($i < $len1) && isset($string2[$j])) {
	        if ($string1[$i] == $string2[$j]) {
	            $similarity++;
	            $i++;
	            $j++;
	        } elseif ($len1 < $len2) {
	            $len1++;
	            $j++;
	        } elseif ($len1 > $len2) {
	            $i++;
	            $len1--;
	        } else {
	            $i++;
	            $j++;
	        }
	    }

	    return round($similarity / $max, 2);
	}

	public function __toString() {
		return $this->_data;
	}
}