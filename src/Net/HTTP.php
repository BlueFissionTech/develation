<?php
namespace BlueFission\Net;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Num;

/**
 * Class HTTP
 * This class provides helper methods for handling HTTP requests and responses.
 */
class HTTP {

	/**
	 * Query function to build a query string from an array of key-value pairs
	 * 
	 * @param array $formdata An array of key-value pairs to be used as the query
	 * @param string $numeric_prefix A prefix to be added to numeric keys in the query array
	 * @param string $key A key to be added to the query array
	 * 
	 * @return string A URL-encoded string representation of the query array
	 */
	static function query( $formdata, $numeric_prefix = '', $key = null ) 
	{
		if (function_exists('http_build_query') && Val::isNull( $key ))
		{
			return http_build_query($formdata, $numeric_prefix);
		}
	     $res = array();
	     foreach ((array)$formdata as $k=>$v) 
	     {
	         $tmp_key = urlencode((is_int($k) && $numeric_prefix) ? $numeric_prefix.$k : $k);
	         if ($key) $tmp_key = $key.'['.$tmp_key.']';
	         if ( is_array($v) || is_object($v) ) 
	         {
	             $res[] = HTTP::query($v, null /* or $numeric_prefix if you want to add numeric_prefix to all indexes in array*/, $tmp_key);
	         } else {
	             $res[] = $tmp_key."=".str_replace('%2F', '/', urlencode($v));
	         }
	         /*
	         If you want, you can write this as one string:
	         $res[] = ( ( is_array($v) || is_object($v) ) ? http_build_query($v, null, $tmp_key) : $tmp_key."=".urlencode($v) );
	         */
	     }
	     $separator = ini_get('arg_separator.output');
	     return implode($separator, $res);
	}

	/**
	 * Check if a URL exists
	 * 
	 * @param string $url The URL to be checked
	 * 
	 * @return bool true if the URL exists, false otherwise
	 */
	static function urlExists(string $url): bool
	{

	    $scheme = parse_url($url, PHP_URL_SCHEME);
	    
	    if ($scheme === false || Arr::has(['http', 'https'], $scheme) === false) {
	        return false;
	    }
	    
	    $host = parse_url($url, PHP_URL_HOST);
	    $port = parse_url($url, PHP_URL_PORT) ?: 80;
	    $fp = @fsockopen($host, $port);
	    if ($fp === false) {
	        return false;
	    }

	    fclose($fp);
	    return true;
	}


	/**
	 * This method returns the domain name of the current website.
	 * If $wholedomain is set to false, it returns the domain name without 'www.'
	 * 
	 * @param bool $wholedomain A flag to determine if the whole domain name should be returned or not.
	 * @return string The domain name of the current website.
	 */
	static function domain( $wholedomain = false ) 
	{
		$domain = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
		if ($domain != '') 
		{
			$domain = (strtolower(Str::sub($domain, 0, 4)) == 'www.' && !$wholedomain ) ? Str::sub($domain, 3) : $domain;
			$port = Str::pos($domain, ':');
			$domain = ($port) ? Str::sub($domain, 0, $port) : $domain;
		}
		return $domain; 
	}

	/**
	 * This method returns the URL of the current website.
	 *
	 * @return string The URL of the current website.
	 */
	static function url()
	{
		$url = '';
		if ( isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) ) {
			$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		}

		return $url;
	}

	/**
	 * This method returns the href value of the current website.
	 * If $href is empty, it returns the current href of the website.
	 * If $doc is set to false, it returns the document root.
	 * 
	 * @param string $href The desired href value to return.
	 * @param bool $doc A flag to determine if the document root or the current href of the website should be returned.
	 * @return string The href value of the current website.
	 */
	static function href($href = '', $doc = true) 
	{
	    if (empty($href)) {
	        if (!defined('PAGE_EXTENSION')) define('PAGE_EXTENSION', '.php');
	        $protocol = (
	        	!empty($_SERVER['HTTPS']) 
	        	&& $_SERVER['HTTPS'] !== 'off' 
	        	|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
	       	)
	       		? "https://" : "http://";
	        $host = $_SERVER['HTTP_HOST'];
	        $request_uri = $_SERVER['REQUEST_URI'];
	        if ($doc === false) {
	            $href = $_SERVER['DOCUMENT_ROOT'];
	        } else {
	            $href = $protocol . $host . $request_uri;
	            if (Str::rpos($href, PAGE_EXTENSION)) {
	                $href = Str::sub($href, 0, Str::rpos($href, PAGE_EXTENSION) + strlen(PAGE_EXTENSION));
	            } elseif (Str::rpos($href, '/')) {
	                $href = Str::sub($href, 0, Str::rpos($href, '/') + strlen('/'));
	            }
	        }
	    }

	    return $href;
	}


	/**
	 * Function to set or retrieve a cookie.
	 * 
	 * @param string $var 			The name of the cookie.
	 * @param mixed $value 			The value to set for the cookie.
	 * @param int $expire 			The number of seconds for the cookie to expire.
	 * @param string $path 			The path for the cookie to be valid on.
	 * @param boolean $secure 		Indicates whether the cookie should only be sent over secure connections.
	 * 
	 * @return mixed 				The value of the cookie if `$value` is null.
	 * 								True on success, False otherwise.
	 */
	static function cookie($var, $value = null, $expire = null, $path = null, $secure = false)
	{
		if (Val::isNull($value))
			return $_COOKIE[$var] ?? null;
		
		$domain = ($path) ? Str::sub($path, 0, Str::pos($path, '/')) : HTTP::domain();
		$dir = ($path) ? Str::sub($path, Str::pos($path, '/'), strlen($path)) : '/';
		$cookiedie = (Num::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
		$cookiesecure = (bool)$secure;
			
		return setcookie ($var, $value, $cookiedie, $dir, $domain, $cookiesecure);
	}

	/**
	 * Function to set or retrieve a session value.
	 * 
	 * @param string $var 			The name of the session variable.
	 * @param mixed $value 			The value to set for the session variable.
	 * @param int $expire 			The number of seconds for the session to expire.
	 * @param string $path 			The path for the session to be valid on.
	 * @param boolean $secure 		Indicates whether the session should only be sent over secure connections.
	 * 
	 * @return mixed 				The value of the session variable if `$value` is null.
	 * 								True on success, False otherwise.
	 */
	static function session($var, $value = null, $expire = null, $path = null, $secure = false)
	{
		if (Val::isNull($value) )
			return $_SESSION[$var] ?? null;
			
		if (session_id() == '') 
		{
			$domain = ($path) ? Str::sub($path, 0, Str::pos($path, '/')) : HTTP::domain();
			$dir = ($path) ? Str::sub($path, Str::pos($path, '/'), strlen($path)) : '/';
			$cookiedie = (Num::isValid($expire)) ? time()+(int)$expire : (int)$expire; //expire in one hour
			$cookiesecure = (bool)$secure;
			
			session_set_cookie_params($cookiedie, $dir, $domain, $cookiesecure);
			session_start();
		}
			
		$status = ($_SESSION[$var] = $value) ? true : false;
		
		return $status;
	}

	/**
	 * Function to encode PHP variables as JSON string
	 *
	 * @param mixed $a
	 * @return string
	 */
	static function jsonEncode($a=false)
	{
		if (function_exists('json_encode'))
		{
			return json_encode($a);
		}
		if (is_null($a)) return 'null';
		if ($a === false) return 'false';
		if ($a === true) return 'true';
		if (is_scalar($a))
		{
			if (is_float($a))
			{
				// Always use "." for floats.
				return floatval(str_replace(",", ".", strval($a)));
			}

			if (is_string($a))
			{
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			}
			else
				return $a;
		}
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a))
		{
		  if (key($a) !== $i)
		  {
		    $isList = false;
		    break;
		  }
		}
		$result = array();
		if ($isList)
		{
		  foreach ($a as $v) $result[] = json_encode($v);
		  return '[' . join(',', $result) . ']';
		}
		else
		{
		  foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
		  return '{' . join(',', $result) . '}';
		}
	}

	/**
	 * Function to redirect to specified URL
	 *
	 * @param string $href
	 * @param array $request_r
	 * @param boolean $ssl
	 * @param string $snapshot
	 * @return void
	 */
	static function redirect($href = '', $request_r = [], $snapshot = '') {
	  $href = HTTP::href($href);
	  $request = ($request_r) ? http_build_query($request_r) : "";
	  $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	  $href = $protocol . str_replace(['http://', 'https://'], '', $href) . (($request != '') ? "?$request" : "");
	  if ($snapshot != '') HTTP::cookie('href_snapshot', $snapshot);
	  header("Location: $href");
	}
}