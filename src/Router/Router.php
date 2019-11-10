<?php

namespace Flow\Router;

use Psr\Http\Message\RequestInterface;
use Flow\Http\Message\Request;
use Flow\Http\Message\Response;
use Flow\Router\Route;
use \Exception;

/**
 * Class Router
 *
 * @package Flow\Router
 *
 * @todo Implement IteratorInterface to iterate matched routes
 * @todo Cache matched routes in memory
 * @todo Refactor Router as an extended RouteCollection
 */
class Router
{
    protected $prefix = '';

    /**
     * @var \Flow\Router\Route[]
     * @todo Refactor with RouteCollection
     */
    protected $routes;

    public function __construct($prefix = '')
    {
        $this->routes = array();
        $this->setPrefix($prefix);
    }

    public function setPrefix($prefix)
    {
        $this->prefix = rtrim($prefix, '/');
        foreach ($this->routes as $route) {
            $route->setPrefix($this->prefix);
        }
        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param Route|string $route
     * @param array|callable|null $options
     * @param callable|null $handler
     * @throws \Exception
     * @return Route
     */
    public function &connect($route, $options = null, callable $handler = null)
    {
        if ($handler === null && is_callable($options)) {
            $handler = $options;
            $options = array();
        }

        if (is_string($route)) {
            $route = new Route($route, $options, $handler);
        } elseif ($route instanceof Route) {
            if ($handler !== null) {
                $route->setHandler($handler);
            }
        } else {
            throw new Exception("Can not connect invalid route");
        }

        $route->setPrefix($this->prefix);

        $hash = spl_object_hash($route);
        $this->routes[$hash] = $route;
        return $this->routes[$hash];
    }

    /**
     * Returns first matching route
     *
     * @param \Flow\Http\Message\Request $request
     * @internal param \Flow\Http\Message\Request $req
     * @return Route
     */
    public function match(RequestInterface $request)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                //print_r("Route found for $path -> " . $r->route . "\n");
                //if ($route->getHandler() instanceof Router) {
                //    $_router = $route->getHandler();
                //    $route = $_router->match($request);
                //}
                return $route;
            }
        }
        return null;
    }

    /**
     * Returns list of matching routes
     *
     * @param RequestInterface $request
     * @return array
     */
    public function matches(RequestInterface $request)
    {
        $matches = array();
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                //print_r("Route found for " . $request->getPath() . " -> " . $route->getRoute() . "\n");
                //var_dump($route->getHandler());
                //if ($route->getHandler() instanceof Router) {
                //    $_router = $route->getHandler();
                //    return $_router->matches($request);
                //}
                array_push($matches, $route);
            }
        }
        return $matches;
    }

}
