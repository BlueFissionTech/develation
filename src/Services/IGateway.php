<?php

namespace BlueFission\Services;

interface IGateway
{
    /**
     * Process a request and modify the arguments
     *
     * @param Request $request The request to process
     * @param mixed $arguments The arguments that may be modified
     *
     * @return GatewayOutcome|null Return a halt outcome to stop mapped execution.
     */
    public function process(Request $request, &$arguments);
}
