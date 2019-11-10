<?php

namespace Flow\Router;

use Psr\Http\Message\RequestInterface;
use \Flow\App\App;
use \Flow\Http\Message\Request;
use \Flow\Http\Message\Response;

class Route
{
    /**
     * Route pattern
     *
     * @var string
     */
    protected $route;

    /**
     * HTTP methods that apply to this route
     *
     * @var array
     */
    protected $methods = array();

    /**
     * Route alias
     *
     * @var string
     */
    protected $name;

    /**
     * Route prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * Params extracted from the route
     *
     * @var array
     */
    protected $params;

    /**
     * @var array|null
     */
    public $paramPatterns = array();

    /**
     * Callable route handler
     *
     * @var callable
     */
    protected $handler;

    /**
     * Compiled Regex pattern of route
     *
     * @var string
     */
    protected $compiled;

    /**
     * Callback stack
     *
     * @var array
     */
    protected $callbacks = array('filter' => array(), 'before' => array(), 'after' => array());

    /**
     * Holds extra data
     * e.g. to satisfy service needs
     *
     * @var array
     */
    protected $extra = array();

    /*** Experimental ***/

    public $schema;

    public $pass;


    public function __construct($route, $options = null, $handler = null)
    {
        $prefix = $name = null;
        $defaults = $patterns = $pass = [];

        if (is_null($handler) && is_callable($options)) {
            $handler = $options;
            $options = [];
        }

        if (is_array($options)) {
            extract($options, EXTR_IF_EXISTS);
        }

        $this->setRoute($route);
        $this->setPrefix($prefix);
        $this->setName($name);

        $this->params = $defaults;
        $this->paramPatterns = $patterns;

        //@todo Move handler verification to the dispatcher. Enables NULL handlers. Dispatcher should decide what to do.
        if ($handler === null) {
            $handler = function () {
                throw new \Exception("No handler has been defined for this route");
            };
        }
        $this->setHandler($handler);

        /*** Experimental ***/
        $this->pass = $pass;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = rtrim((string) $prefix, '/');
        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }


    public function setRoute($route)
    {
        $route = $this->normalizePath($route);
        $this->route = $route;
        return $this;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Set route handler
     *
     * @param callable $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        //@TODO Move is_callable check to route dispatcher. Enables NULL callbacks. Dispatcher should decide what to do.
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException(sprintf("Handler for Route %s has to be a callable", $this->route));
        }

        $this->handler = $handler;
        return $this;
    }

    /**
     * Get route handler
     *
     * @return callable
     */
    public function getHandler()
    {
        return $this->handler;
    }

    public function getCompiled()
    {
        if ($this->compiled === null) {
            $this->compile();
        }
        return $this->compiled;
    }


    public function getParams($key = null)
    {
        if ($key === null) {
            return $this->params;
        }

        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        return null;
    }

    public function getPassVars()
    {
        //@todo Implement me
        return $this->getParams();
    }

    public function setExtra($scope, $extra = array(), $merge = true)
    {
        if (isset($this->extra[$scope]) && $merge) {
            $this->extra[$scope] = array_merge($this->extra[$scope], $extra);
        } else {
            $this->extra[$scope] = $extra;
        }
        return $this;
    }

    public function getExtra($scope)
    {
        if (isset($this->extra[$scope])) {
            return $this->extra[$scope];
        }

        return array();
    }

    //*****************************************************************
    //*** SHORTCUT WRAPPERS ***
    //*****************************************************************

    public function methods()
    {
        $methods = func_get_args();
        if (count($methods) < 1) {
            throw new \InvalidArgumentException("No route methods defined");
        } elseif (count($methods) == 1) {
            $methods = explode('|', (string) $methods[0]);
        }

        $this->methods = $methods;
        return $this;
    }

    public function name($name)
    {
        return $this->setName($name);
    }

    //*****************************************************************
    //*** ROUTE HANDLING ***
    //*****************************************************************

    /**
     * Generates a schema description of the current route
     * 
     * @return $this
     * @throws \Exception
     */
    public function describe()
    {
        $this->schema = array();

        $trimmed = rtrim(ltrim($this->route, '/'), '/');
        if (strlen($trimmed) < 1) {
            $this->schema = array();
            return $this;
        }

        $sIdx = 0;
        $parts = explode('/', $trimmed);
        for ($i = 0; $i < count($parts); $i++) {

            $part = $parts[$i];
            $matches = array();

            if ($part == '*' || $part == '**') {
                if ($i != count($parts) - 1) {
                    throw new \Exception('Invalid Wildcard position.');
                }
                $this->schema[$sIdx++] = array(
                    'type' => 'wildcard',
                    'mode' => ($part == '*') ? 'non-greedy' : 'greedy',
                    'pattern' => ($part == '*') ? '(.+)/' : '(.*)',
                );

            } elseif (preg_match_all('@{([\?\w]+)}@', $part, $matches)) {
                foreach ($matches[1] as $match) {

                    //if (preg_match('@^\{(.*)\}$@', $part, $matches)) {

                        $optional = false;
                        $name = $part = $match;

                        // optional params
                        if ($part[0] == "?") {
                            $optional = true;
                            $name = $part = substr($part, 1);
                        }

                        // named params
                        if (preg_match('@^([0-9]+)$@', $part, $matches)) {
                            $pattern = (isset($this->paramPatterns[$name]))
                                ? '(' . $this->paramPatterns[$name] . ')'
                                : '([\w]+)';

                            $this->schema[$sIdx++] = array(
                                'type' => 'index',
                                'name' => $name,
                                'pattern' => $pattern,
                                'optional' => $optional
                            );

                        } elseif (preg_match('@^([\w]+)$@', $part, $matches)) {
                            $pattern = (isset($this->paramPatterns[$name]))
                                ? '(' . $this->paramPatterns[$name] . ')'
                                : '([\w]+)';

                            $this->schema[$sIdx++] = array(
                                'type' => 'named',
                                'name' => $name,
                                'pattern' => ($optional) ? $pattern .'?' : $pattern,
                                'optional' => $optional
                            );
                        } else {
                            //debug("No match for part $part\n");
                            /*
                            $this->schema[$sIdx++] = array(
                                'type' => 'static',
                                'name' => null,
                                'pattern' => '(' . preg_quote($part, '/') . ')',
                            );
                            */
                        }
                    //}


                    /*
                    $pattern = (isset($this->paramPatterns[$name]))
                        ? '(' . $this->paramPatterns[$name] . ')'
                        : '([\w]+)';

                    $this->schema[$sIdx++] = array(
                        'type' => 'named',
                        'name' => $name,
                        'pattern' => $pattern,
                        'optional' => $optional
                    );
                    */
                }

            }


        }
        //debug($this->schema);
    }

    /**
     * Compile route pattern into a regex pattern
     *
     * @throws \Exception
     */
    public function compile()
    {
        $this->describe();

        $compiled = $this->route;
        foreach ($this->schema as $params) {
            switch($params['type']) {
                case "index":
                case "named":
                    if ($params['optional']) {
                        $compiled = preg_replace('@\{\?' . $params['name'] . '\}\/@i', $params['pattern'] . '/?', $compiled);
                    } else {
                        $compiled = preg_replace('@\{' . $params['name'] . '\}@i', $params['pattern'], $compiled);
                    }
                    break;
                case "wildcard":
                    if ($params['mode'] == 'non-greedy') {
                        $compiled = preg_replace('@\/\*\/@i', '/' . $params['pattern'], $compiled);
                    } elseif ($params['mode'] == 'greedy') {
                        $compiled = preg_replace('@\/\*\*\/@i', '/' . $params['pattern'], $compiled);
                    } else {
                        //throw new \Exception('Unknown wildcard mode');
                        debug('Router:compile() error: Unknown wildcard mode');
                    }
                    break;
                case "static":
                    break;
            }
        }

        // escape path separators
        $compiled = preg_replace('@\/@', '\\\/', $compiled);

        //debug("Route: " . $this->route . " - Compiled: $compiled");
        $this->compiled = $compiled;
    }

    //*****************************************************************
    //*** ROUTE MATCHING ***
    //*****************************************************************

    /**
     * Matches current route against Request
     *
     * @param Request $request
     * @return bool
     */
    public function matches(RequestInterface $request)
    {
        if ($this->matchPath($request->getUri()->getPath()) && $this->matchMethod($request->getMethod())) {
            return true;
        }
    }

    /**
     * @param $path
     * @deprecated Use matchPath() instead
     */
    public function match($path)
    {
        return $this->matchPath($path);
    }

    public function matchMethod($method)
    {
        if (empty($this->methods)) {
            return true;
        }

        return in_array($method, $this->methods);
    }

    /**
     * Match given $path against route
     *
     * @param $path
     */
    public function matchPath($path)
    {
        // match prefix
        if ($this->prefix) {
            if (substr($path, 0, strlen($this->prefix)) != $this->prefix) {
                throw new \Exception(sprintf('The path %s is not in prefix scope %s', $path, $this->prefix));
            } else {
                $path = substr($path, strlen($this->prefix));
            }
        }

        // compile route into regex expression
        $this->compile();

        // normalize given path
        $path = $this->normalizePath($path);

        //debug("$path -> " . $this->compiled . "\n");

        // match
        if (!preg_match('@^' . $this->compiled . '$@i', $path, $matches)) {
            //debug(sprintf("%s did not match %s (%s)", $path, $this->route, $this->compiled));
            return false;
        }

        // reset
        $this->params = array();

        // assign named
        //debug($this->compiled);
        //debug($matches);
        array_shift($matches);
        for ($i = 0; $i < count($matches); $i++) {
            switch($this->schema[$i]['type']) {
                case 'static':
                    break;
                case 'wildcard':
                    if ($this->schema[$i]['mode'] == 'greedy') {
                        $this->params[] = $matches[$i];
                    } else {
                        foreach (explode('/', rtrim($matches[$i], '/')) as $part) {
                            $this->params[] = $part;
                        }
                    }
                    break;
                case 'named':
                case 'index':
                default:
                    $this->params[$this->schema[$i]['name']] = $matches[$i];
                    break;

            }
        }

        return true;
    }

    /**
     * Generate route's url path with given params
     *
     * @param array $params Route params
     * @return bool|string
     */
    public function generate($params = array())
    {
        $this->describe();

        $url = $this->route;
        $url = rtrim($url, '/');

        // strip off wildcard
        $wildcard = false;
        if (substr($url, -2) == "**") {
            $url = substr($url, 0, -2);
            $wildcard = 'greedy';
        } elseif (substr($url, -1) == "*") {
            $url = substr($url, 0, -1);
            $wildcard = 'non-greedy';
        }
        foreach ($params as $key => $val) {
            $pattern = '/\{' . $key . '\}/i';
            if (!empty($val) && preg_match($pattern, $url)) {
                $url = preg_replace($pattern, $val, $url);
                unset($params[$key]);
            }
        }

        // unresolved named params
        if (preg_match('/\{(.*)\}/i', $url)) {
            return false;

        // append remaining wildcard params
        } elseif (!empty($params) && $wildcard != false) {
            $url .= join('/', $params);

        // no remaining params but wildcard enabled
        } elseif (empty($params) && $wildcard != false) {
            return false;

        // unmatched params
        } elseif (!empty($params)) {
            return false;
        }


        $url = rtrim($url, '/');
        $url .= '/';

        //debug("Generated URL for route: $_url -> $url\n");

        return $url;
    }

    /**
     * Path normalization
     *
     * - Ensure leading slash
     * - Ensure trailing slash
     * - Strip script filename
     *
     * @param $path
     * @return string
     */
    protected function normalizePath($path)
    {
        // ensure non-empty route
        if (strlen($path) < 1) {
            $route = "/";
        }

        // ensure leading slash
        if (substr($path, 0, 1) != "/") {
            $path = "/" . $path;
        }

        // strip filename
        //@TODO read script filename from environment
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        if (substr($path, 1, strlen($scriptName)) == $scriptName) {
            $path = substr($path, strlen($scriptName) + 1);
        }

        // ensure trailing slash
        if (substr($path, -1, 1) != "/") {
            $path = $path . "/";
        }

        return $path;
    }

    //*****************************************************************
    //*** DISPATCH ***
    //*****************************************************************

    /**
     * @param \Flow\App $app
     * @return Response
     * @throws \Exception
     */
    /*
    public function dispatch(\Flow\App $app)
    {
        $handler = $this->getHandler();
        $args = $this->getPassVars();
        array_unshift($args, $app);

        //@todo Use reflection on the handler and check if it requires App instance as first argument or not

        if (is_object($handler) && method_exists($handler, '__invoke')) {
            ob_start();
            $result = call_user_func_array($handler, $args);
            $buffer = ob_get_clean();

            if ($result === null && strlen($buffer) > 0) {
                $result = $buffer;
            }
        } else {
            throw new \Exception('Route handler is not callable');
        }

        if ($result instanceof Response) {
            return $result;
        }
        return new Response((string) $result);
    }

    public function __invoke(App $app)
    {
        $before = $this->trigger('before');
        if ($before instanceof Response) {
            return $before;
        }

        $before = $app->applyHook('route.before', $this);
        if ($before instanceof Response) {
            return $before;
        }

        $result = $this->dispatch($app);

        $this->trigger('after');
        $app->applyHook('route.after', $this);

        return $result;
    }
    */

    //*****************************************************************
    //*** HOOKS ***
    //*****************************************************************

    public function filter(callable $callable)
    {
        $this->callbacks['filter'][] = $callable;
        return $this;
    }

    public function before(callable $callable)
    {
        $this->callbacks['before'][] = $callable;
        return $this;
    }

    public function after(callable $callable)
    {
        $this->callbacks['after'][] = $callable;
        return $this;
    }

    public function trigger($name)
    {
        if (!isset($this->callbacks[$name])) {
            throw new \Exception('Unknown callback ' . $name);
        }

        $args = func_get_args();
        array_shift($args);
        array_unshift($args, $this);

        foreach ($this->callbacks[$name] as $callable) {
            $result = call_user_func_array($callable, $args);
            if ($result !== null) {
                return $result;
            }
        }
    }

    //*****************************************************************
    //*** EXTRAS ***
    //*****************************************************************

    //@TODO Make extra method binding magic
    public function auth($settings = array())
    {
        return $this->setExtra('auth', $settings);
    }

    //@TODO Make extra method binding magic
    public function security($settings = array())
    {
        return $this->setExtra('security', $settings);
    }

}
