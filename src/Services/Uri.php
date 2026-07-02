<?php

namespace BlueFission\Services;

use BlueFission\Arr;
use BlueFission\Net\HTTP;
use BlueFission\Str;

/**
 * Class Uri
 *
 * This class provides functionality for parsing and matching URLs.
 *
 * @package BlueFission\Services
 */
class Uri
{
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
    private $_valueToken = '$';

    /**
     * Uri constructor.
     *
     * @param string $path The URL path to parse. If not provided, the current URL will be used.
     */
    public function __construct(string $path = '')
    {
        $url = $path != '' ? $path : HTTP::url();

        $request = Str::make(HTTP::urlPath($url) ?? '')->trim('/');
        $this->path = $request->val();

        $this->parts = $request->split('/')->val();
    }

    /**
     * Matches a test URI against the current URL path.
     *
     * @param string $testUri The URI to test against.
     *
     * @return bool Returns true if the test URI matches the current URL path, false otherwise.
     */
    public function match($testUri)
    {
        $cleanTestUri = Str::make($testUri)->trim('/');

        if ($cleanTestUri->match($this->path)) {
            return true;
        }

        $uri_parts = $cleanTestUri->split('/');
        $parts = Arr::make($this->parts);

        if ($uri_parts->count() == $parts->count()) {
            for ($i = 0; $i < $uri_parts->count(); $i++) {
                if (!$this->compare_parts($uri_parts[$i], $this->parts[$i])) {
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
    public function matchAndReturn($testUri)
    {
        $cleanTestUri = Str::make($testUri)->trim('/');

        if ($cleanTestUri->match($this->path)) {
            return $testUri;
        }

        $uri_parts = $cleanTestUri->split('/');
        $parts = Arr::make($this->parts);

        if ($uri_parts->count() == $parts->count()) {
            for ($i = 0; $i < $uri_parts->count(); $i++) {
                if (!$this->compare_parts($uri_parts[$i], $this->parts[$i])) {
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
    public function buildArguments($uriSignature)
    {
        $arguments = Arr::make();

        $cleanUri = Str::make($uriSignature)->trim('/');

        $uri_parts = $cleanUri->split('/');
        $parts = Arr::make($this->parts);

        if ($uri_parts->count() == $parts->count()) {
            for ($i = 0; $i < $uri_parts->count(); $i++) {
                if (Str::startsWith($uri_parts[$i], $this->_valueToken)) {
                    $arguments[Str::sub($uri_parts[$i], 1)] = $this->parts[$i];
                }
            }
        }

        return $arguments->val();
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
        if ($firstPart == $secondPart) {
            return true;
        }
        if (Str::startsWith($firstPart, $this->_valueToken)
            || Str::startsWith($secondPart, $this->_valueToken)) {
            return true;
        }

        return false;
    }

}
