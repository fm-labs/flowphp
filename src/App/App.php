<?php

namespace Flow\App;

use Flow\Http\Message\Request;
use Flow\Object\ConfigInterface;
use Flow\Template\Template;
use Flow\Container\Container;
use Flow\Http\Environment;
use Flow\Http\Message\Stream\StringStream;
use Flow\Http\Message\Response;
use Flow\Http\Message\Response\ErrorResponse;
use Flow\Http\Message\Uri;
use Flow\App\Middleware\CorsMiddleware;
use Flow\App\Middleware\ErrorMiddleware;
use Flow\App\Middleware\RequestMapperMiddleware;
use Flow\App\Middleware\RoutingMiddleware;
use Flow\Http\Server\MiddlewareQueue;
use Flow\Http\Server\ResponseEmitterInterface;
use Flow\Http\Server\ResponseEmitterTrait;
use Flow\Object\SingletonTrait;
use Flow\Router\Router;
use Flow\Router\Route;
use Flow\Flow;
use Flow\_View\View;
use FmLabs\Uri\UriFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Flow Application
 *
 * @package Flow\App
 *
 * @property Configuration $config
 * @property MiddlewareQueue $middlewares
 * @property ServerRequestInterface $request
 * @property ResponseInterface $response
 * @property Router $router
 */
class App extends Container implements RequestHandlerInterface, ResponseFactoryInterface, ResponseEmitterInterface
{
    //use SingletonTrait;
    use ResponseEmitterTrait;

    //private static $singletonProtectGet = true; //@see SingletonTrait
    //static private $singletonProtectSet = true; //@see SingletonTrait

    protected $defaultConfig = [
        'ignoreReady' => true
    ];

    /**
     * Map of registered event callbacks.
     *
     * @var array
     * @todo Use an EventManager instead
     */
    protected $events = [
        'app.before' => [],
        'app.after' => [],
        'route.filter' => [],
        'route.before' => [],
        'route.after' => []
    ];

    protected $plugins = [];

    /**
     * Init the application
     *
     * - Init Configuration
     * - Init Router
     * - Init MiddlewareQueue
     *
     * @param array|Environment $env
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        //static::setInstance($this);

        $this->config = new Configuration($config);
        $this->router = new Router();
        $this->middlewares = new MiddlewareQueue();
        $this->request = new Request();
        $this->response = new Response();

        $this->setup();
    }

    public function setup()
    {
        // Insert the ErrorMiddleware first, to catch errors in all subsequent middlewares
        $this->middleware(new ErrorMiddleware($this));
        $this->middleware(new RequestMapperMiddleware($this));
        $this->middleware(new CorsMiddleware($this));

        $this->factory('template', function () {
            $template = (new Template($this->config('template') ?? []));
            $this->trigger('template.create', [], $template);
            
            return $template;
        });
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function middleware(MiddlewareInterface $middleware)
    {
        $this->middlewares->add($middleware);

        return $this;
    }

    //*****************************************************************
    //*** MAGIC ACCESSOR ***
    //*****************************************************************

    /**
     * Magic object getter.
     *
     * @param $key
     * @return mixed
     */
    public function __get($id)
    {
        return $this->get($id);
    }

    /**
     * Magic object setter.
     *
     * @param $id
     * @param $val
     * @return void
     */
    public function __set($id, $val)
    {
        $this->register($id, $val);
    }

    /**
     * Magic object checker
     *
     * @param $id
     * @return bool
     */
    public function __isset($id)
    {
        return $this->has($id);
    }

    /**
     * Magic object dropper.
     *
     * @param $id
     */
    public function __unset($id)
    {
        $this->unregister($id);
    }

    /**
     * Magic object invocation spawns new object instances from the factory.
     * If the spawned instance implements the `ConfigInterface` and
     * and the object id matches an app config key, the corresponding configuration
     * will be injected
     *
     */
    public function __invoke()
    {
        // routing-middleware-as-last-middleware - work-around
        // this is suboptimal for _future_ subsequent/nested handle() calls
        // as the RoutingMiddleware would be re-attached each time.
        $this->middleware(new RoutingMiddleware($this));

        return $this;
    }

    /**
     * Magic call as container getter
     */
    public function __call($method, $args)
    {
        $obj = $this->spawn($method, $args);
        if ($obj instanceof ConfigInterface) {
            $obj->config($this->config($method));
        }

        return $obj;
    }

    //*****************************************************************
    //*** Quick Access Helper ***
    //*****************************************************************
    public function config($key = null, $value = null)
    {
        if ($key !== null && is_string($key) && $value !== null) {
            $key = [$key => $value];
        }
        return $this->config->config($key);
    }

    /**
     * Attach a plugin.
     *
     * The plugin handler must be a callable object.
     * The only argument passed to the handler is the instance of the application.
     *
     * @param string $pluginName The name of the plugin
     * @param null|callable $plugin
     * @return |null
     * @throws \Exception
     */
    public function plugin(string $pluginName, $plugin = null)
    {
        if ($plugin !== null) {
            if (!is_callable($plugin)) {
                $plugin = function () use ($pluginName) {
                    throw new \InvalidArgumentException(sprintf("App: Uncallable plugin '%s'", $pluginName));
                };
            }

            return $this->plugins[$pluginName] = call_user_func($plugin, $this);
        }

        // @TODO Throw new MissingPluginException
        return ($this->plugins[$pluginName]) ?? null;
    }

    //*****************************************************************
    //*** DISPATCHING ***
    //*****************************************************************

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->response = ($this->response) ?: new Response();

        // @TODO Map ServerRequest to AppRequest

        // create a response based on the current request
        // deprecated: Use RequestMapperMiddleware instead
        //$this->response = (new RequestMapper($request))
        //    ->createResponse();

        // before
        $before = $this->trigger('app.before');
        if ($before instanceof ResponseInterface) {
            return $this->response = $before;
        }

        reset($this->middlewares->queue);
        $result = $this->middlewares->handle($request);
        if ($result instanceof ResponseInterface) {
            $this->response = $result;
        }

        // after
        $after = $this->trigger('app.after');
        if ($after instanceof ResponseInterface) {
            $this->response = $after;
        }

        return $this->response;
    }



    //*****************************************************************
    //*** RESPONSE FACTORY ***
    //*****************************************************************

    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response($code, $reasonPhrase));
    }


    //*****************************************************************
    //*** CONTROLLER HANDLING ***
    //*****************************************************************

    /**
     * @param $name
     * @return mixed
     * @todo Refactor with controller factory
     */
    public function controller($name)
    {
        $class = Flow::className("Controller", $name, "Controller");
        $controller = new $class($this);

        return $controller;
    }


    //*****************************************************************
    //*** STATE CONTROL ***
    //*****************************************************************

    /**
     * Exit the application
     *
     * @param int|string $status. An integer is used as return code, a string prints the text and exists with status 0.
     * @return void
     */
    public function stop($status = 0)
    {
        exit($status);
    }

    /**
     * ABORT the current request immediately
     * and send response with a 500 HTTP status code,
     * then exit the application NORMALLY
     *
     * @return void
     */
    public function abort()
    {
        $this->error("ABORTED", 500, 0);
    }

    /**
     * Send an error message with an appropriate http status code
     *
     * @param $message
     * @param int $httpStatus
     * @todo Print error message to STDERR as well
     * @return Response|null
     */
    public function error($message, $httpStatus = 500)
    {
        $response = new ErrorResponse($message, $httpStatus);
        //$response = $this->response ?? new Response();
        return $this->response = $response
            ->withStatus($httpStatus, $message)
            ->withBody(new StringStream(sprintf("An error occured: %s", (string) $message)));
    }

    public function forward($to)
    {
        // @todo Implement request forwarding
        //$this->request()->setPath($to);
        //$this->dispatch($this->router());
    }


    /**
     * HTTP Redirect Response
     *
     * @param $url
     * @param int $status
     */
    public function redirect($url, $status = 302)
    {
        // local redirect
        /*
        if (is_string($url) && strpos('/', $url) === 0) {
            $request = Request::get($url);
            return $this->handle($request);
        }
        */

        $response = new \Flow\Http\Message\Response\RedirectResponse($this->uri($url), $status);
        $this->send($response);
        $this->stop();
    }

    //*****************************************************************
    //*** RESPONSE WRAPPERS ***
    //*****************************************************************


    /**
     * Send response
     * @param ResponseInterface $response
     * @return int
     */
    public function send(ResponseInterface $response = null)
    {
        $response = ($response) ?: $this->response;
        if (!$response) {
            throw new \RuntimeException("App: No response to send");
        }

        $this->sendResponse($response);
        $this->stop();
    }

    public function text(string $str)
    {
        return $this->response
            ->withHeader('Content-Type', 'text/plain')
            ->withBody(new StringStream($str));
    }

    public function html(string $html)
    {
        return $this->response
            ->withHeader('Content-Type', 'text/html')
            ->withBody(new StringStream($html));
    }

    /**
     * @param array|string $data
     */
    public function json($data)
    {
        $encode = true;
        if (is_string($data) || is_object($data) || method_exists($data, '__toString')) {
            $data = (string)$data;
            // check if it's a valid json string FORMAT (does not mean it's valid JSON!)
            if (substr($data, 0, 1) == "{" && substr($data, -1) == "}") {
                $encode = false;
            // all other strings will be converted to an array
            } else {
                $data = ['content' => $data];
            }
        } elseif (!is_array($data)) {
            throw new \InvalidArgumentException("Invalid data: Can not convert to JSON");
        }

        if ($encode) {
            $data = json_encode($data, JSON_PRETTY_PRINT); // @todo disable pretty printing
        }

        return $this->response
            ->withoutHeader('Content-Type')
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream($data));
    }

    /**
     * @param string $xmlStr
     * @return ResponseInterface
     * @todo Add XML document support
     */
    public function xml(string $xmlStr)
    {
        return $this->response
            ->withHeader('Content-Type', 'application/xml')
            ->withBody(new StringStream($xmlStr));
    }

    /**
     * File Response
     *
     * @throws \Exception
     * @todo Implement sendFile
     */
    public function file()
    {
        //$this->response = new \Flow\Http\FileResponse()
        throw new \Exception("Implement me: " . __FUNCTION__);
    }

    /**
     * Stream Response
     *
     * @throws \Exception
     * @todo Implement sendStream
     */
    public function stream()
    {
        //$this->response = new \Flow\Http\StreamResponse()
        throw new \Exception("Implement me: " . __FUNCTION__);
    }

    //*****************************************************************
    //*** ROUTING ***
    //*****************************************************************

    /**
     * Connect / Map a route
     *
     * @param $route
     * @param null $options
     * @param null $handler
     * @return Route
     */
    public function connect($route, $options = null, $handler = null)
    {
        return $this->router->connect($route, $options, $handler);
    }

    /**
     * Connect a collection of routes *experimental*
     *
     * @param $prefix
     * @param $router
     * @return Route
     * @throws \InvalidArgumentException
     * @todo Implement group routes
     */
    public function group($prefix, $router)
    {
        if (is_a($router, 'Closure')) {
            $router = call_user_func($router, new Router($prefix));
        }

        if (!($router instanceof Router)) {
            throw new \InvalidArgumentException("Instance of Router expected");
        }

        return $this->connect($prefix . '/**', $router);
    }

    /**
     * Connect / Mount another Flow application *experimental*
     *
     * @param $prefix
     * @param $app
     * @return Route
     * @throws \InvalidArgumentException
     * @todo Implement app mounting
     * @todo Implement closure mounting
     */
    public function mount($prefix, $app)
    {
        //if (is_a($app, 'Closure')) {
        //    $app = call_user_func($app, new App($this->env));
        //}

        if (!($app instanceof App)) {
            throw new \InvalidArgumentException("Instance of Flow\\App expected");
        }

        $app->router->setPrefix($prefix);

        return $this->connect($prefix . '/**', $app);
    }


    //*****************************************************************
    //*** EVENTS ***
    //*****************************************************************

    /**
     * @param $event
     * @param callable $callable
     * @return $this
     * @todo Refactor with event manager
     */
    public function on($event, callable $callable)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = array();
        }
        $this->events[$event][] = $callable;
        return $this;
    }

    /**
     * @param $event
     * @return mixed
     * @todo Refactor with event manager
     */
    public function trigger($event, array $data = [], $subject = null)
    {
        //$args = func_get_args(); // use all other args as callback function args
        //array_shift($args); // remove the event name
        //array_unshift($args, $this); // add $this as first callback function arg
        if ($subject === null) {
            $subject =& $this;
        }

        foreach ($this->events[$event] as $callable) {
            $result = call_user_func($callable, $event, $subject, $data);
            if ($result !== null) {
                return $result;
            }
        }
    }

    /**
     * @param callable $callable
     * @return $this
     * @todo Refactor with event manager
     */
    public function before(callable $callable)
    {
        return $this->on('app.before', $callable);
    }

    /**
     * @param callable $callable
     * @return $this
     * @todo Refactor with event manager
     */
    public function after(callable $callable)
    {
        return $this->on('app.after', $callable);
    }

    //*****************************************************************
    //*** VIEW ***
    //*****************************************************************

    public function view()
    {
        $template = $this->template();
        return new TemplateView($template);
    }

    /**
     * @param null $viewClass
     * @throws \InvalidArgumentException
     * @return View
     */
    public function oldview($viewClass = null)
    {
        if ($viewClass) {
            if ($viewClass instanceof View) {
                $this->view = function () use ($viewClass) {
                    return $viewClass;
                };
            }

            if (!is_string($viewClass)) {
                throw new \InvalidArgumentException("View class must be an instance of View or a View class name");
            }

            if ($this->has('view') && get_class($this->view) != $viewClass) {
                $this->view = function (App $app) use ($viewClass) {
                    $view = new $viewClass($this);
                    $view->merge($app['view']);
                    return $view;
                };
            } else {
                $this->view = function () use ($viewClass) {
                    return new $viewClass($this);
                };
            }
        } elseif (!isset($this->view)) {
            $this->view = function () use ($viewClass) {
                //@todo do not hardcode view class name
                return new \Flow\_View\FlowView($this);
            };
        }

        return $this->view;
    }

    //*****************************************************************
    //*** URL HELPERS ***
    //*****************************************************************

    /**
     * Build new URI
     *
     * @param array|string|UriInterface $uri
     * @return UriInterface
     * @deprecated
     */
    public function uri($uri)
    {
        // base uri is the uri of current request, without query and fragment
        $base = $this->request->getUri()
            ->withQuery("")
            ->withFragment("");

        if (is_array($uri)) {
            $uri = UriFactory::fromComponents($uri);
        } elseif (is_string($uri)) {
            $uri = UriFactory::fromString($uri);
        } else {
            $uri = UriFactory::fromUri($uri);
        }

        $uri
            ->withScheme($base->getScheme())
            ->withHost($base->getHost())
            ->withPort($base->getPort())
            ->withUserInfo($base->getUser(), $base->getUserPass())
        ;

        return $uri;
    }

    /**
     * @param null $url
     * @return string
     * @todo Refactor with UriBuilder
     */
    public function url($url = null)
    {
        if ($url === null) {
            $url = $this->request->getUri()->getPath();
        }

        if (is_array($url)) {
            if (isset($url['?'])) {
                $query = $url['?'];
                unset($url['?']);
            }
            if (isset($url['#'])) {
                $fragment = $url['#'];
                unset($url['#']);
            }
            $url = join('/', $url);
            if (isset($query)) {
                $url .= '?' . http_build_query($query);
            }
            if (isset($fragment)) {
                $url .= '#' . $fragment;
            }
        }

        if (preg_match('/^http(s)?:\/\//', $url)) {
            return $url;
        }

        $hostUrl = $this->request->getUri()->getHost();
        $baseUrl = '/'; // $this->getBaseUrl();

        if ($url[0] === '/') {
            $url = ltrim($url, '/');
        } elseif (strpos($url, './') === 0) {
            $routerPrefix = $this->activeRouter->getPrefix();
            $url = ltrim($routerPrefix, '/') . substr($url, 1);
        } else {
            $routerPrefix = $this->activeRouter->getPrefix();
            $prefix = ($routerPrefix) ? ltrim($routerPrefix, '/') . '/' : '';
            $url = $prefix . ltrim($url, '/');
        }

        return $hostUrl . $baseUrl . $url;
    }


    /**
     * Invoke Application
     * Enables nested applications which can share services through the parent app, if provided
     *
     * @param App $parent
     * @todo Implement parent functionality (overriding the container etc.)
    public function __invoke(App $parent = null)
    {
    $this->run();
    }
     */

    /**
     * Run
     *
     * Setup error handler, freeze application, create request from environment,
     * handle request, send response, exits the application
     * @throws \Exception
     * @deprecated
    public function run()
    {
    //@todo freeze the application (= no routes / middlewares can be added)
    //@todo set error handler
    //@todo exception handling
    //@todo output buffering?

    $request = new Request($this->env);

    debug($request);
    var_dump($request);
    //$request = $serverRequestFactory->createServerRequest(null, null, $this->env);

    try {
    $this->handle($request);
    } catch (\Exception $e) {
    throw $e;
    }

    $this->response->send();

    //@todo restore error handler

    exit;
    }
     */
}
