<?php
namespace BlueFission;

class DevString extends DevValue implements IDevValue {
	/**
	 *
	 * @var string $_type is used to store the data type of the object
	 */
	protected $_type = "string";

	/**
     * @var string MD5 hash algorithm
     */
    const MD5 = 'md5';

	/**
     * @var string SHA hash algorithm
     */
    const SHA = 'sha1';

    /**
	 * Constructor to initialize value of the class
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null, $force = false ) {
		$value = is_string( $value ) ? $value : ( ($force && $value != null) ? (string)$value : null );
		parent::__construct($value);
	}

    /**
     * Checks is value is a string
     *
     * @param mixed $value
     * 
     * @return bool
     */
    public function _is( ): bool
    {
    	return is_string($this->_data);
	}

	/**
	 * Generate a random string
	 * 
	 * @param int $length The length of the desired random string. Default is 8.
	 * @param bool $symbols If set to true, special characters are included in the random string. Default is false.
	 * 
	 * @return DevString
	 */
	public function _random(int $length = 8, bool $symbols = false): DevString {
		$alphanum = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if ($symbols) $alphanum .= "~!@#\$%^&*()_+=";

		if ( $this->_data == "" ) {
			$this->_data = $alphanum;
		}
		$rand_string = '';
		for($i=0; $i<$length; $i++) {
			$rand_string .= $this->_data[rand(0, strlen($this->_data)-1)];
		}

		$this->alter($rand_string);

		return $this;
	}

	// https://www.uuidgenerator.net/dev-corner/php
	/**
     * Generates a version 4 UUID
     *
     * @return DevString
     */
	public function _uuid4(): DevString
	{
	    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
	    if (!function_exists('random_bytes')) {
            throw new Exception('Function random_bytes does not exist');
        }
	    $data = $this->_data ?? random_bytes(16);
	    assert(strlen($data) == 16);

	    // Set version to 0100
	    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	    // Set bits 6-7 to 10
	    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

	    // Output the 36 character UUID.
	    $string = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

	    $this->alter($string);

	    return $this;
	}

	/**
	 * Truncates a string to a given number of words using space as a word boundary.
	 * 
	 * @param int $limit The number of words to limit the string to. Default is 40.
	 * @return DevString
	 */
	public function _truncate(int $limit = 40): DevString
	{
		$string = trim( $this->_data );
		$string_r = explode(' ', $string, ($limit+1));
		if (count($string_r) >= $limit && $limit > 0) array_pop($string_r);
		$string = implode (' ', $string_r);

		$this->alter($string);
		
		return $this;
	}

	/**
	 * Check if the current string matches the input string
	 *
	 * @param string $str2 The string to compare with the current string
	 *
	 * @return bool True if the two strings match, false otherwise
	 */
	public function _match(string $str2): bool
	{
		$str1 = $this->_data;
		return ($str1 == $str2);
	}

	/**
	 * Encrypt a string
	 *
	 * @param string $mode The encryption mode to use. Can be 'md5' or 'sha1'. Default is 'md5'
	 * @return DevString
	 */
	public function _encrypt(string $mode = null): DevString {
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
		
		$this->alter($string);

		return $this;
	}

	/**
	 * Returns the position of the first occurrence of a substring in a string
	 *
	 * @param string $needle The substring to search for
	 *
	 * @return int The position of the first occurrence of $needle in the string, or -1 if not found
	 */
	public function _strpos(string $needle): int {
		return strpos($this->_data, $needle);
	}

	/**
	 * Returns the position of the first occurrence of a case-insensitive substring in a string
	 *
	 * @param string $needle The substring to search for
	 *
	 * @return int The position of the first occurrence of $needle in the string, or -1 if not found
	 */
	public function _stripos(string $needle): int {
		return stripos($this->_data, $needle);
	}

	// Reverse strpos
	/**
     * Finds the position of the last occurrence of a substring in a string
     *
     * @param string $needle
     *
     * @return int
     */
	public function _strrpos(string $needle): int {
		$haystack = $this->_data;
		$i = strlen($haystack);
		while ( substr( $haystack, $i, strlen( $needle ) ) != $needle ) 
		{
			$i--;
			if ( $i < 0 ) return false;
		}
		return $i;
	}

	/**
	 * Returns the length of the string
	 *
	 * @return int The length of the string
	 */
	public function _length(): int {
		return strlen($this->_data);
	}

	/**
	 * Converts all characters of the string to lowercase
	 *
	 * @return DevString
	 */
	public function _lower(): DevString {
		$string = strtolower($this->_data);
		$this->alter($string);

		return $this;
	}

	/**
	 * Converts all characters of the string to uppercase
	 *
	 * @return DevString
	 */
	public function _upper(): DevString {
		$string =strtoupper($this->_data);
		$this->alter($string);

		return $this;
	}

	/**
	 * Capitalizes the first letter of each word in the string
	 *
	 * @return DevString
	 */
	public function _capitalize(): DevString {
		$string = ucwords($this->_data);
		$this->alter($string);

		return $this;
	}

	/**
	 * Repeats the string the specified number of times
	 *
	 * @param int $times The number of times to repeat the string
	 *
	 * @return DevString
	 */
	public function _repeat(int $times): DevString {
		$string = str_repeat($this->_data, $times);

		$this->alter($string);

		return $this;
	}

	/**
	 * Searches for a specified value and replaces it with another value
	 *
	 * @param string $search The value to search for
	 * @param string $replace The value to replace the search value with
	 *
	 * @return DevString
	 */
	public function _replace(string $search, string $replace): DevString {
		$string = str_replace($search, $replace, $this->_data);

		$this->alter($string);

		return $this;
	}

	/**
	 * Returns a substring of the string, starting from a specified position
	 *
	 * @param int $start The starting position of the substring
	 * @param int|null $length The length of the substring. If not specified, the rest of the string will be returned
	 *
	 * @return string The substring
	 */
	public function _substring(int $start, int $length = null): string {
		return substr($this->_data, $start, $length);
	}

	/**
	 * Trims whitespace from the beginning and end of the string
	 *
	 * @return DevString
	 */
	public function _trim(): DevString {
		$string = trim($this->_data);

		$this->alter($string);

		return $this;
	}


	/**
	 * Check if a string exists in another string
	 * 
	 * @param string $needle The string to search for
	 * @return boolean True if the needle is found in the haystack, false otherwise
	 */
	public function _has(string $needle): bool {
		$haystack = $this->_data;
		return (\strpos($haystack, $needle) !== false);
	}

	/**
     * Calculates the similarity between two strings
     *
     * @param string $string
     *
     * @return float
     */
	public function _similarityTo(string $string): float {

		// via vasyl at vasyltech dot com from https://secure.php.net/manual/en/function.similar-text.php
		$string1 = $this->_data;
		$string2 = $string;

		if (empty($string1) || empty($string2)) {
            throw new Exception('Input string(s) cannot be empty');
        }

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

	/**
	 * Returns the string representation of the class instance.
	 * @return string
	 */
	public function __toString(): string {
		return $this->_data;
	}
}