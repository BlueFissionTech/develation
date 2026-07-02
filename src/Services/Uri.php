<?php

namespace BlueFission\Services;

use BlueFission\Arr;
use BlueFission\Flag;
use BlueFission\Net\HTTP;
use BlueFission\Num;
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
        $url = Str::isNotEmpty($path) ? $path : HTTP::url();

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

        if ($this->partsMatch($uri_parts, $parts)) {
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

        if ($this->partsMatch($uri_parts, $parts)) {
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

        if ($this->samePartCount($uri_parts, $parts)) {
            $index = Num::make(0);

            foreach ($uri_parts->val() as $uriPart) {
                if (Str::startsWith($uriPart, $this->_valueToken)) {
                    $arguments[Str::sub($uriPart, 1)] = $parts[$index->val()];
                }

                $index->increment();
            }
        }

        return $arguments->val();
    }

    /**
     * Determine whether a URI signature and request path have compatible parts.
     *
     * @param Arr $uriParts
     * @param Arr $parts
     * @return bool
     */
    private function partsMatch(Arr $uriParts, Arr $parts): bool
    {
        if (!$this->samePartCount($uriParts, $parts)) {
            return false;
        }

        $matches = Flag::make(true);
        $index = Num::make(0);

        foreach ($uriParts->val() as $uriPart) {
            if (!$this->compare_parts($uriPart, $parts[$index->val()])) {
                $matches->val(false);
                break;
            }

            $index->increment();
        }

        return $matches->isTrue();
    }

    /**
     * Determine whether two URI part lists have the same size.
     *
     * @param Arr $uriParts
     * @param Arr $parts
     * @return bool
     */
    private function samePartCount(Arr $uriParts, Arr $parts): bool
    {
        return $uriParts->count() == $parts->count();
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
