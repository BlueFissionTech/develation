<?php

namespace BlueFission\Net;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\CommonClassesStrategy;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Http\Discovery\ClassDiscovery;

class HTTPClientFactory
{
    public static function create(): HTTPClient
    {
        $curl = new Curl(); // Assuming Curl class is configured appropriately
        return new HTTPClient($curl);
    }
}

// Register the factory with HTTPlug Discovery
ClassDiscovery::appendStrategy(CommonClassesStrategy::class);
HttpClientDiscovery::prependStrategy(CommonClassesStrategy::class);
HttpClientDiscovery::prependCandidate(HTTPClient::class, HTTPClientFactory::class, []);
