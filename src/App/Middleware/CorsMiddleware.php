<?php


namespace Flow\App\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\App\Middleware;

class CorsMiddleware extends Middleware
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
        $this->app->response = $this->app->response
            ->withHeader('Access-Control-Allow-Origin', '*')
            //->withHeader('Access-Control-Allow-Credentials', '*')
            //->withHeader('Access-Control-Expose-Headers', '*')
            //->withHeader('Access-Control-Max-Age', '*')
            ->withHeader('Access-Control-Allow-Methods', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
        ;

        return $handler->handle($request);
    }
}