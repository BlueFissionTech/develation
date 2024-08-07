<?php

namespace BlueFission\Services;

// @TODO: make other classes extend this base class
abstract class Client extends Service {
	protected ?Curl $_curl;
	protected ?string $_baseUrl;
	protected ?string $_apiKey;
	protected $_client;

	public function __construct()
	{
		$this->_curl = new Curl([
			'method' => 'post',
		]);
	}

	public function get(string $endpoint = '')
	{
		$target = implode('/', [$this->_baseUrl, $endpoint]);
		
		$this->_curl->config('target', $target);
		$this->_curl->open();
		$this->_curl->query();
		$response = $this->_curl->getResult();
		$this->_curl->close();

		return $response;
	}

	public function post($data, string $endpoint = '')
	{
		$target = implode('/', [$this->_baseUrl, $endpoint]);

		$this->_curl->config('target', $target);
		$this->_curl->open();
		$this->_curl->query(http_build_query($data));
		$response = $this->_curl->getResult();
		$this->_curl->close();

		return $response;
	}
}