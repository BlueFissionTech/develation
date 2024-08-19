<?php

namespace BlueFission\Services;

use BlueFission\Net\HTTP;

/**
 * Class Uri
 *
 * This class provides functionality for parsing and matching URLs.
 *
 * @package BlueFission\Services
 */
class Uri {
	/**
	 * The path of the URL.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * The parts of the URL path.
	 *
	 * @var array
	 */
	public $parts;

	/**
	 * The token used to denote a value in the URL path.
	 *
	 * @var string
	 */
	private $valueToken = '$';

	/**
	 * Uri constructor.
	 *
	 * @param string $path The URL path to parse. If not provided, the current URL will be used.
	 */
	public function __construct( string $path = '' ) 
	{
		$url = $path != '' ? $path : HTTP::url();

		$request = trim(parse_url($url, PHP_URL_PATH), '/');
		$this->path = $request;

		$request_parts = explode( '/', $request );
		$this->parts = $request_parts;
	}

	/**
	 * Matches a test URI against the current URL path.
	 *
	 * @param string $testUri The URI to test against.
	 *
	 * @return bool Returns true if the test URI matches the current URL path, false otherwise.
	 */
	public function match( $testUri )
	{
		$cleanTestUri = trim($testUri, '/');

		if ( $cleanTestUri == $this->path ) {
			return true;
		}

		$uri_parts = explode( '/', $cleanTestUri );

		if ( count( $uri_parts ) == count( $this->parts ) ) {
			for ( $i = 0; $i < count($uri_parts); $i++ ) {
				if ( !$this->compare_parts($uri_parts[$i], $this->parts[$i]) ) {
					return false;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Matches a test URI against the current URL path and returns the test URI if it matches.
	 *
	 * @param string $testUri The URI to test against.
	 *
	 * @return string|bool Returns the test URI if it matches the current URL path, false otherwise.
	 */
	public function matchAndReturn( $testUri )
	{
		$cleanTestUri = trim($testUri, '/');

		if ( $cleanTestUri == $this->path ) {
			return $testUri;
		}

		$uri_parts = explode( '/', $cleanTestUri );

		if ( count( $uri_parts ) == count( $this->parts ) ) {
			for ( $i = 0; $i < count($uri_parts); $i++ ) {
				if ( !$this->compare_parts($uri_parts[$i], $this->parts[$i]) ) {
					return false;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Build the arguments based on the uri signature
	 *
	 * @param string $uriSignature
	 * @return array
	 */
	public function buildArguments( $uriSignature )
	{
		$arguments = [];

		$cleanUri = trim($uriSignature, '/');

		$uri_parts = explode( '/', $cleanUri );

		if ( count( $uri_parts ) == count( $this->parts ) ) {
			for ( $i = 0; $i < count($uri_parts); $i++ ) {
				if ( strpos($uri_parts[$i], $this->valueToken) === 0 ) {
					$arguments[ substr($uri_parts[$i], 1) ] = $this->parts[$i];
				}
			}
		}

		return $arguments;
	}

	/**
	 * Compare the parts of the uri
	 *
	 * @param string $firstPart
	 * @param string $secondPart
	 * @return boolean
	 */
	private function compare_parts($firstPart, $secondPart)
	{
		if ( $firstPart == $secondPart ) {
			return true;
		}
		if ( strpos($firstPart, $this->valueToken) === 0 
			|| strpos($secondPart, $this->valueToken) === 0 ) {
			return true;
		}

		return false;
	}

}