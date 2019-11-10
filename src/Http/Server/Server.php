<?php
declare(strict_types=1);

namespace Flow\Http\Server;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\App\App;
use Flow\Http\Environment;
use Flow\Object\StaticFactoryTrait;

class Server implements ResponseEmitterInterface
{
    use StaticFactoryTrait;
    use ResponseEmitterTrait;

    /**
     * @var \Flow\Http\Environment
     */
    protected $env;

    /**
     * Server constructor.
     */
    public function __construct()
    {
        $this->setEnvironment(Environment::fromGlobals());
    }

    /**
     * Set the server environment.
     *
     * @param Environment|ServerRequestFactoryInterface $env
     * @return $this
     */
    public function setEnvironment(ServerRequestFactoryInterface $env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Generate and handle the server request and
     * emitting the obtained response to the client.
     *
     * @param App|RequestHandlerInterface $handler
     */
    public function run(RequestHandlerInterface $handler)
    {
        try {
            $request = $this->env->createServerRequest("GET", "/", []);
            $response = $handler->handle($request);
            $this->sendResponse($response);
        } catch (\Exception $ex) {
            // @todo Error logging
            die("[CRITICAL] Unhandled Server Error: " . $ex->getMessage());
        }
    }
}