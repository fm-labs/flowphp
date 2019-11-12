<?php
namespace Flow\Core;

use Flow\Http\Message\Request;
use Flow\Http\Message\Response;
use Flow\Router\LegacyRoute;
use Flow\Router\Router;

/**
 * Class Dispatcher
 * @package Flow\Core
 * @deprecated Use MiddlewareQueue instead
 */
class Dispatcher {

    /**
     * @var App
     */
    private $app;


    /**
     * @var Router
     */
    private $activeRouter;

    /**
     * @var LegacyRoute
     */
    private $activeRoute;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param Router $router
     * @return Response
     * @throws \Exception
     */
    public function dispatch(Router $router)
    {
        $this->activeRouter = $router;

        // @todo Get only first match from router
        foreach ($router->matches($this->app->request()) as $route) {

            return $this->handleRoute($route);

            //if ($result === null) {
            //    continue;
            //}
        }

        // No route matched
        throw new \Exception('No route found for ' . $this->app->request()->getPath());
    }


    /**
     * Each route has a route handler.
     *
     * Route handlers can be:
     * a) A closure / callable method
     * b) A router instance
     *
     * The route handler will be invoked with a reference to the App instance as first argument,
     * and and the passed args from the route subsequentially.
     *
     * The result of the route handler can be:
     * a) A response object
     * b) Any object that implements the __toString interface
     * c) A string
     * d)
     *
     * @param LegacyRoute $route
     * @return Response
     * @throws \Exception
     */
    private function handleRoute(LegacyRoute $route)
    {


        //@todo Use Event system
        //@todo Use reflection on the handler and check if it requires App instance as first argument or not
        //@todo Encapsulate handler invocation in closure / dispatcher
        //@todo Implement output buffered responses
        //@todo Router should handle nested routers itself. Return the final route!

        $handler = $route->getHandler();
        $request = $this->app->request();
        $response = $this->app->response();

        // If the handler is a route, dispatch that router
        if ($handler instanceof Router) {
            return $this->dispatch($handler);
        }

        // Check if handler is callable
        if (!is_callable($handler)) {
            throw new \Exception('Route handler is not callable');
        }

        // Working with this route now
        $this->activeRoute = $route;


        // Inject route params into request object
        $request->params = $route->getParams();

        // Trigger events 'before'
        $before = $route->trigger('before');
        if ($before instanceof Response) {
            return $before;
        }

        $before = $this->app->applyHook('route.before', $route);
        if ($before instanceof Response) {
            return $before;
        }

        // Extract passed args from route
        // and inject App instance as first argument
        $args = $route->getPassVars();
        array_unshift($args, $this->app);


        $useBuffered = false;
        try {

            // Invoke route handler with output buffering enabled
            ob_start();

            $result = call_user_func_array($handler, $args);
            switch (true) {
                case $result instanceof Response:
                    $response = $result;
                    break;
                case is_object($result) && method_exists($result, '__toString'):
                    // objects that include a __toString method are casted to string as body
                    $response->setBody((string) $result);
                    break;
                case is_object($result) && method_exists($result, '__invoke'):
                case is_callable($result):
                    // callable/invokeable results get invoked and MUST return a Response object
                    $callableResultDispatcher = function() use ($result) {
                        return call_user_func($result);
                    };
                    $_result = $callableResultDispatcher();
                    if ($_result instanceof Response) {
                        $response = $_result;
                    } else {
                        throw new \BadFunctionCallException('Callable dispacher result is not instance of Response');
                    }
                case is_string($result):
                    // strings are set as response body
                    $response->setBody($result);
                    break;
                case is_bool($result) && ($result === true):
                    // boolean TRUE enables buffered result
                    $useBuffered = true;
                    break;
                case is_null($result):
                    // do nothing
                    break;
                default:
                    throw new \Exception('Route handler result malformed');
            }

            $buffer = ob_get_clean();

            if ($this->app->debug === true) {
                // prepend buffered content in debug mode
                $response->setBody($buffer . $response->getBody());

            } elseif ($useBuffered) {
                // set output buffer as response content, if flag is set
                $response->setBody($useBuffered);
            }

        } catch (\Exception $ex) {
            $buffer = ob_get_clean();

            //@TODO Logging
            throw $ex;
        }


        // after
        $after = $route->trigger('after', $response);
        if ($after instanceof Response) {
            return $after;
        }

        $after = $this->app->applyHook('route.after', $route, $response);
        if ($after instanceof Response) {
            return $after;
        }

        return $response;
    }


} 