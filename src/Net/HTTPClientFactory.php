<?php

namespace BlueFission\Net;

use BlueFission\Connections\Curl;

class HTTPClientFactory
{
    public static function create(): HTTPClient
    {
        return new HTTPClient(new Curl());
    }
}
