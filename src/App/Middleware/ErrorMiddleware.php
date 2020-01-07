<?php
namespace Flow\App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\Http\Message\Stream\StringStream;
use Flow\App\Middleware;

class ErrorMiddleware extends Middleware
{

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Exception | \Error $ex) {
            /*
            $errorMessage = sprintf(
                "Error [%s]: [File %s:%s] %s",
                get_class($ex),
                $ex->getFile(),
                $ex->getLine(),
                $ex->getMessage()
            );
            */

            $error = [
                'exception' => get_class($ex),
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
            ];

            return $this->app->response
                ->withStatus(500, "")
                ->withBody(new StringStream(json_encode($error, JSON_PRETTY_PRINT)));
        }
    }
}