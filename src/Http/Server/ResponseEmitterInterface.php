<?php

namespace Flow\Http\Server;


use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    /**
     * Emits a response.
     *
     * Typically a `ResponseInterface` is produced by a `RequestHandlerInterface` handling
     * a `ServerRequestInterface`.
     *
     * @param ResponseInterface $response
     * @return int Number of written bytes (only body, without header)
     */
    public function sendResponse(ResponseInterface $response): int;
}
