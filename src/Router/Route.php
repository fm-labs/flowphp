<?php

namespace Flow\Router;

use Flow\Http\Message\Request;
use Psr\Http\Message\RequestInterface;

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
    protected $methods = [];

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
    protected $paramPatterns = [];

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
     * Callbacks
     *
     * @var array
     */
    protected $callbacks = array('filter' => array(), 'before' => array(), 'after' => array());

    /**
     * @var array List of arguments that will be passed to the handler method
     */
    protected $pass = [];

    /**
     * @var array
     */
    private $info = [];

    private $passArgs = [];

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

        $this->route = $route;
        $this->name = $name;
        $this->handler = $handler;
        $this->params = $defaults;
        $this->paramPatterns = $patterns;
        $this->pass = $pass;
        //$this->prefix = trim((string) $prefix, '/');
        $this->setPrefix($prefix);
    }

    public function setPrefix($prefix)
    {
        $this->prefix = trim((string) $prefix, '/');
        $this->compile();

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getRoute()
    {
        return $this->route;
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
    protected function setHandler($handler)
    {
        //@todo Move handler verification to the dispatcher. Enables NULL handlers. Dispatcher should decide what to do.
        if ($handler === null) {
            $handler = function () {
                throw new \Exception("No handler has been defined for this route");
            };
        }
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
        return $this->passArgs;
    }

    //*****************************************************************
    //*** ROUTE HANDLING ***
    //*****************************************************************

    /**
     * Compile route pattern into a regex pattern
     *
     * @throws \Exception
     */
    protected function compile()
    {
        $prefix = ($this->prefix) ? $this->prefix . '/' : '';
        $route = $this->normalizePath($prefix . $this->route);

        //$parts = (array_map([$this, 'normalizePath'], [$this->prefix, $this->route]));
        //$parts = array_filter($parts, function ($part) {
        //    return (strlen($part) > 0);
        //});
        //$route = join('/', $parts);

        $info = [];

        // find named params
        $compiled = preg_replace_callback_array([
            '@{\?([\w\-]+)}@' => function ($matches) use (&$info) {
                //debug("found optional param: " . $matches[1]);
                $pattern = $this->paramPatterns[$matches[1]] ?? '[\w\-]+';
                $info[] = ['name' => $matches[1], 'optional' => true];
                return '(' . $pattern . ')?';
            },
            '@{([\w\-]+)}@' => function ($matches) use (&$info) {
                //debug("found required param: " . $matches[1]);
                $pattern = $this->paramPatterns[$matches[1]] ?? '[\w\-]+';
                $info[] = ['name' => $matches[1], 'optional' => false];
                return '(' . $pattern . ')';
            },
            '@/(\*\*)@' =>  function ($matches) use (&$info) {
                //debug("found greedy wildcard");
                $info[] = ['name' => null, 'wildcard' => true, 'greedy' => true];
                return '/(.+)';
            },
            '@/(\*)@' =>  function ($matches) use (&$info) {
                //debug("found non-greedy wildcard");
                $info[] = ['name' => null, 'wildcard' => true, 'greedy' => false];
                return '/([\w\-]+)';
            },
        ], $route);

        $this->compiled =  $compiled;
        $this->info = $info;
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
    public function match(RequestInterface $request)
    {
        $this->params = [];

        if (
            $this->matchMethod($request->getMethod())
            && $this->matchPath($request->getUri()->getPath())
        ) {
            return true;
        }

        return false;
    }

    protected function matchMethod($method)
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
    protected function matchPath($path)
    {
        if ($this->compiled === null) {
            throw new \RuntimeException("Route MUST be compiled first");
        }

        $path = $this->normalizePath($path);
        // match prefix
        /*
        if ($this->prefix) {
            if (substr($path, 0, strlen($this->prefix)) != $this->prefix) {
                throw new \Exception(sprintf('The path %s is not in prefix scope %s', $path, $this->prefix));
            } else {
                $path = substr($path, strlen($this->prefix));
            }
        }
        */

        // normalize given path
        //debug("$path -> " . $this->compiled . "\n");


        // match
        if (!preg_match('@^' . $this->compiled . '$@i', $path, $matches)) {
            debug(sprintf("path:%s did not match route:%s:%s  [ %s ]", $path, $this->prefix, $this->route, $this->compiled));
            return false;
        }

        // parse params
        $this->params = [];

        debug(sprintf("path:%s MATCHED route:%s:%s  [ %s ]", $path, $this->prefix, $this->route, $this->compiled));
        //debug($matches);
        //debug($this->info);

        if (count($matches) - 1 != count($this->info)) {
            debug("MISSMATCH!");
        }

        array_shift($matches);
        for ($i = 0; $i < count($matches); $i++) {
            $_info = $this->info[$i];
            $_name = $_info['name'] ?? null;
            $_val = $matches[$i];
            if ($_info['wildcard'] ?? null) {
                $this->params['_wildcard'][] = $_val;
                continue;
            }

            //if (in_array($_info['name'], $this->pass)) {
            //if (is_string($_info['name'])) {
                array_push($this->passArgs, $_val);
            //}

            $this->params[$_info['name']] = $_val;
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
        // ensure leading slash
        $path = /*'/' . */trim($path, '/');

        $path = str_replace("//", "/", $path);
        $path = str_replace("\/\/", "/", $path);
        // ensure trailing slash
        //if (substr($path, -1, 1) != "/") {
        //    $path = $path . "/";
        //}

        return $path;
    }

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
}
