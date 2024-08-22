<?php

namespace BlueFission\Services;

// @TODO: make other classes extend this base class
abstract class Client extends Service {
	protected ?Curl $curl;
	protected ?string $baseUrl;
	protected ?string $apiKey;
	protected $client;

	public function __construct()
	{
		$this->curl = new Curl([
			'method' => 'post',
		]);
	}

	public function get(string $endpoint = '')
	{
		$target = implode('/', [$this->baseUrl, $endpoint]);
		
		$this->curl->config('target', $target);
		$this->curl->open();
		$this->curl->query();
		$response = $this->curl->getResult();
		$this->curl->close();

		return $response;
	}

	public function post($data, string $endpoint = '')
	{
		$target = implode('/', [$this->baseUrl, $endpoint]);

		$this->curl->config('target', $target);
		$this->curl->open();
		$this->curl->query(http_build_query($data));
		$response = $this->curl->getResult();
		$this->curl->close();

		return $response;
	}
}