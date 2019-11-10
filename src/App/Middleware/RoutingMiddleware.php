<?php
namespace Flow\App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\App\App;
use Flow\Http\Message\Stream\StringStream;
use Flow\Http\Message\Response;
use Flow\App\Middleware;
use Flow\Http\Server\RequestMapper;
use Flow\Router\Route;
use Flow\Router\Router;

class RoutingMiddleware extends Middleware
{

    /**
     * @var Route
     */
    protected $activeRoute;

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->app->router;

        // @todo Get only first match from router
        foreach ($router->matches($request) as $route) {
            $result = $this->handleRoute($route, $request);
            if ($result instanceof ResponseInterface) {
                return $result;
            }
        }

        return $handler->handle($request);
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
     * @param Route $route
     * @return Response
     * @throws \Exception
     */
    private function handleRoute(Route $route, ServerRequestInterface $request)
    {
        $response = $this->app->response;

        // Working with this route now
        $this->activeRoute = $route;

        // Inject route params as request attributes
        foreach ($route->getParams() as $k => $v) {
            $request->withAttribute($k, $v);
        }

        // 'before' events
        $before = $this->app->trigger('route.before', $route);
        if ($before instanceof Response) {
            return $before;
        }

        $before = $route->trigger('before');
        if ($before instanceof ResponseInterface) {
            return $before;
        }

        // Extract args from route
        // then validate and execute route handler
        $handler = $route->getHandler();
        if (!is_callable($handler)) {
            throw new \Exception('Route handler is not callable');
        }
        $handlerArgs = $route->getPassVars();
        $response = $this->executeHandler($request, $response, $handler, $handlerArgs);

        // 'after' events
        $after = $route->trigger('after', $response);
        if ($after instanceof ResponseInterface) {
            return $after;
        }

        $after = $this->app->trigger('route.after', $route, $response);
        if ($after instanceof Response) {
            return $after;
        }

        return $response;
    }

    /**
     * @param Route $route
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed|ResponseInterface
     * @throws \Exception
     */
    private function executeHandler(ServerRequestInterface $request, ResponseInterface $response, callable $handler, array $args = [])
    {
        //@todo Output buffering
        //@todo Handle nested handlers, routers, apps and plugins
        //@TODO Error Logging

        //$useBuffered = false;
        //ob_start();
        try {
            // If the handler is a route, dispatch that router
            //if ($handler instanceof Router) {
            //    #$this->router = $handler;
            //    #return $this->process($request);
            //    #return $handler;
            //}

            // bind the app instance as $this in route handlers
            if ($handler instanceof \Closure) {
                $handler = \Closure::bind($handler, $this->app);
            }
            $result = call_user_func_array($handler, $args);
            //debug($result);

            // RESPONSE results
            if ($result instanceof ResponseInterface) {
                return $result;
            // STRING results or objects that can be converted to string
            } elseif (is_string($result) || (is_object($result) && method_exists($result, '__toString'))) {
                $response = $response->withBody(new StringStream((string)$result));
            // NULL results
            } elseif (is_null($result)) {
                $response = $response->withBody(new StringStream(""));
            // CALLABLE results (nested handlers)
            } elseif (is_callable($result)) {
                $response = $this->executeHandler($result, $route, $request, $response);
            // INVALID results
            } else {
                throw new \RuntimeException("Router: Malformed handler result");
            }

        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            //$buffer = ob_get_flush();
            //$buffer = ob_get_clean();
        }

        return $response;
    }
}