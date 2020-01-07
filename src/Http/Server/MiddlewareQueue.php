<?php

namespace Flow\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\Http\Exception\HttpException;

class MiddlewareQueue implements RequestHandlerInterface
{
    /**
     * @var []\Psr\Http\Server\MiddlewareInterface List of unprocessed middleware instances
     */
    public $queue = [];

    /**
     * Add a middleware object or class.
     *
     * @param string|\Psr\Http\Server\MiddlewareInterface $middleware
     * @todo Implement priority
     * @todo Drop support for string arguments. Only accept MiddlewareInterface instance.
     */
    public function add($middleware)
    {
        if (is_string($middleware)) {
            $middleware = $this->loadMiddleware($middleware);
        }

        if (!($middleware instanceof MiddlewareInterface)) {
            throw new \InvalidArgumentException("Invalid Http middleware");
        }

        array_push($this->queue, $middleware);
        reset($this->queue);
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var MiddlewareInterface $middleware */
        $middleware = current($this->queue);

        if (empty($this->queue) || $middleware === false) {
            throw HttpException::notFound(sprintf("No handler found for '%s'", $request->getUri()));
        }

        //$middleware = current($this->queue);
        //$middleware = array_shift($this->queue);
        //debug("invoke middleware: " . get_class($middleware));
        next($this->queue);
        return $middleware->process($request, $this);
    }

    /**
     * Load middleware object from class name
     *
     * @param $className
     * @return \Psr\Http\Server\MiddlewareInterface|null
     * @deprecated This method will be removed, as soon as class loader support will be drop.
     * @todo Drop class loader support
     */
    protected function loadMiddleware($className)
    {
        $class = null;
        if (class_exists($className)) {
            $class = new $className();
        }

        return $class;
    }
}
