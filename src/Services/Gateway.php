<?php

namespace BlueFission\Services;

use BlueFission\Services\Request;
use BlueFission\Services\IGateway;

/**
 * Class Gateway
 *
 * Implements the IGateway interface to process requests.
 *
 * @package BlueFission\Services
 */
class Gateway implements IGateway
{
    /**
     * Gateway constructor.
     */
    public function __construct()
    {
    }

    /**
     * Process the Request and update the arguments.
     *
     * @param Request $request The request to process.
     * @param mixed $arguments The arguments to be updated.
     *
     * @return void
     */
    public function process(Request $request, &$arguments)
    {
        // Do Something
    }
}
