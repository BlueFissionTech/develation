<?php
namespace BlueFission\Services;

use BlueFission\Net\HTTP;

class Uri {
	public $path;
	public $parts;

	private $_valueToken = '$';

	public function __construct( string $path = '' ) 
	{
		$url = $path != '' ? $path : HTTP::url();

		
		$request = trim(parse_url($url, PHP_URL_PATH), '/');
		$this->path = $request;

		$request_parts = explode( '/', $request );
		$this->parts = $request_parts;
	}

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

	public function buildArguments( $uriSignature )
	{
		$arguments = [];

		$cleanUri = trim($uriSignature, '/');

		$uri_parts = explode( '/', $cleanUri );

		if ( count( $uri_parts ) == count( $this->parts ) ) {
			for ( $i = 0; $i < count($uri_parts); $i++ ) {
				if ( strpos($uri_parts[$i], $this->_valueToken) === 0 ) {
					$arguments[ substr($uri_parts[$i], 1) ] = $this->parts[$i];
				}
			}
		}

		return $arguments;
	}

	private function compare_parts($firstPart, $secondPart)
	{
		if ( $firstPart == $secondPart ) {
			return true;
		}
		if ( strpos($firstPart, $this->_valueToken) === 0 
			|| strpos($secondPart, $this->_valueToken) === 0 ) {
			return true;
		}

		return false;
	}
}