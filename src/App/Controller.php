<?php

namespace Flow\App;

use Flow\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Controller
 *
 * @property \Flow\Http\Message\Request $request
 * @property \Flow\Http\Message\Response $response
 */
abstract class Controller implements RequestHandlerInterface
{
    /**
     * @var \Flow\App\App
     */
    protected $app;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Default constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->response = &$this->app->response;
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
     * Invoke controller action directly.
     * @throws NotFoundException
     */
    public function __invoke($method, array $args = [])
    {
        return $this->invoke($method, $args);
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @throws NotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $action = $request->getAttribute('action', 'index');
        $params = $request->getAttributes();
        unset($params['action']);

        $this->app->request = $request;
        $this->invoke($action, $params);

        return $this->response;
    }

    /**
     * @param string $action
     * @param array $params
     * @return mixed
     * @throws NotFoundException
     * @throws \Exception
     */
    protected function invoke(string $action, array $params = [])
    {
        $r = new \ReflectionClass($this);
        if (
            !$r->hasMethod($action) // the method has to exist in current controller
            || !$r->getMethod($action)->isPublic() // and has to be public
            || in_array($action, get_class_methods(self::class)) // built-in controller methods are not invokable
        ) {
            throw new NotFoundException("Action '$action' not found in controller class" . get_class($this));
        }

        $this->response = Dispatcher::executeHandler(
            $this->app,
            $this->app->request,
            $this->app->response,
            [$this, $action],
            $params
        );
    }
}
