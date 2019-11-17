<?php
namespace Flow\App\Middleware;

use Flow\App\App;
use Flow\App\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\Http\Message\Stream\StringStream;
use Flow\Http\Message\Response;
use Flow\App\Middleware;
use Flow\Router\Route;
use Flow\Router\Router;

class RoutingMiddleware extends Middleware
{

    /**
     * @var Route
     */
    protected $activeRoute;

    /**
     * @var Router
     */
    protected $activeRouter;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->activeRouter = $this->app->router;
    }

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
        $router =& $this->activeRouter;

        /*
        foreach ($router->matches($request) as $route) {
            $result = $this->handleRoute($route, $request);
            if ($result instanceof ResponseInterface) {
                return $result;
            }
            break;
        }
        */
        //print_routes($this->app->router->getRoutes());

        $route = $router->match($request);
        if ($route) {
            $result = $this->handleRoute($route, $request);
            if ($result instanceof ResponseInterface) {
                return $result;
            }
        }

        // @Todo remove debug statements
        //print_routes($router->getRoutes());
        //throw new \Exception(sprintf("No route match for '%s'", $request->getUri()));

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
            $request = $request->withAttribute($k, $v);
        }

        // 'before' events
        $before = $this->app->trigger('route.before', ['route' => $route]);
        if ($before instanceof ResponseInterface) {
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
        $response = Dispatcher::executeHandler($this->app, $request, $response, $handler, $handlerArgs);

        // 'after' events
        $after = $route->trigger('after', ['request' => $request, 'response' => $response]);
        if ($after instanceof ResponseInterface) {
            return $after;
        }

        $after = $this->app->trigger('route.after', ['route' => $route, 'request' => $request, 'response' => $response]);
        if ($after instanceof ResponseInterface) {
            return $after;
        }

        return $response;
    }
}
