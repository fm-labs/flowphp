<?php

namespace Flow\App;

use Flow\App\App;
use Flow\_View\View;

/**
 * Class Controller
 *
 * @property \Flow\Http\Message\Request $request
 * @property \Flow\Http\Message\Response $response
 */
class Controller
{
    /**
     * @var \Flow\App\App
     */
    protected $app;

    /**
     * @var string Name of current action
     */
    protected $action;

    /**
     * @var string Name of View class
     */
    protected $viewClass;

    /**
     * Default constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Magic direct access to action methods
     *
     * @param $method
     * @param $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        return call_user_func_array(array($this->app, $method), $params);
    }

    /**
     * Magic controller invocation
     *
     * @param string $action
     * @param null $params
     */
    public function __invoke($action = 'default', $params = null)
    {
        $this->invoke($action, $params);
    }

    /**
     * Magic object-to-string method invokes default action
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->invoke();
    }

    /**
     * Public wrapper to invoke controller actions
     *
     * @param string $action
     * @param null|array|mixed $params Magic method params
     *      a) If NULL, params from request are used (if any).
     *      b) If $params is an array, the array content is used as method params. Other arguments are ignored.
     *      c) If $params is not an array and any other value than NULL, then all arguments are added to the param list.
     * @return mixed
     */
    public function invoke($action = 'default', $params = null)
    {
        //@TODO dispatch controller callbacks

        $this->action = $action;

        if ($params === null) {
            $params = $this->app->request->params();
        } elseif (func_num_args() >= 2 && is_array(func_get_arg(1))) {
            $params = func_get_arg(1);
        } elseif (func_num_args() > 1) {
            $params = [];
            for ($i = 1; $i < func_num_args(); $i++) {
                $params[] = func_get_arg($i);
            }
        } else {
            $params = [];
        }
        return $this->invokeAction($action, $params);
    }

    /**
     * Invoke controller action method by name
     *
     * @param $action
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    protected function invokeAction($action, $params = [])
    {
        $method = $action . 'Action';
        if (!method_exists($this, $method)) {
            //@TODO throw MethodNotFoundException or BadMethodCallException
            throw new \Exception("Action $method is not defined in controller " . get_class($this));
        }

        return call_user_func_array(array($this, $method), $params);
    }

    /**
     * Method stub for the 'default' action.
     * Should be replaced in subclasses
     *
     * @return string|mixed
     */
    public function defaultAction()
    {
        //@TODO Only use defaultAction stub in debug mode or make it abstract
        return "This is the default action for this controller: " . get_called_class();
    }

    /**
     * Wrapper function to retrieve view instance from app
     *
     * @param null $viewClass
     * @return View
     */
    public function view($viewClass = null)
    {
        return $this->app->view($viewClass);
    }

    /**
     * Set view var
     *
     * @param $key
     * @param null $val
     * @internal param $var
     * @return $this
     * @deprecated Use view()->set() instead
     */
    public function set($key, $val = null)
    {
        $this->view()->set($key, $val);
        return $this;
    }

    /**
     * Get view var
     *
     * @param $key
     * @internal param $var
     * @return null
     * @deprecated Use view()->get() instead
     */
    public function get($key)
    {
        return $this->view()->get($key);
    }

    /**
     * Generate a new instance of given controller class
     *
     * @param App $app
     * @param $controllerClass
     * @return callable
     */
    public static function create(App $app, $controllerClass)
    {
        //@TODO check if object is instance of controller
        return new $controllerClass($app);
    }

    /**
     * Invoke an action method on a new factorized instance of given controller class
     *
     * @param App $app
     * @param $controllerClass
     * @param $action
     * @param null|array $params
     * @return mixed
     */
//    public static function invoke(App $app, $controllerClass, $action = 'default', $params = null)
//    {
//        $c = static::create($app, $controllerClass);
//        return $c($action, $params);
//    }
}
