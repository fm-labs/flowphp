<?php

namespace Flow\App;

use Flow\Http\Message\Stream\StringStream;
use Flow\Http\StatusCode;
use Flow\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Dispatcher
 * @package Flow\App
 */
class Dispatcher
{
    /**
     * @param Route $route
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed|ResponseInterface
     * @throws \Exception
     */
    public static function executeHandler(
        App $app,
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $handler,
        array $args = []
    ) {
        //@todo Output buffering
        ob_start();
        $buffer = null;
        try {
            // bind the app instance as $this in route handlers
            // and execute
            if ($handler instanceof \Closure) {
                $handler = \Closure::bind($handler, $app);
            }
            $result = call_user_func_array($handler, $args);
            //debug($result);

            // result handling
            if ($result instanceof ResponseInterface) {
                // RESPONSE results: Return
                $response = $result;
            } elseif ($result instanceof RequestHandlerInterface) {
                // REQUEST-HANDLER results: Pass the request to the nested handler recursively
                if ($result instanceof App) {
                    // For nested Apps we have to update the router prefix.
                    // The path of the current request is set as the root path of the nested App.
                    $result->router->setPrefix($request->getUri()->getPath());
                }
                $response = $result->handle($request);
            } elseif (is_callable($result)) {
                // CALLABLE-HANDLER results: Execute the dispatcher recursively
                $response = self::executeHandler($app, $request, $response, $result, $args);
            } elseif (is_string($result) || (is_object($result) && method_exists($result, '__toString'))) {
                // STRING results or objects that can be converted to string: Apply result as body contents
                $response = $response->withBody(new StringStream((string)$result));
            } elseif (is_null($result)) {
                // NULL results: Send an empty response with 'No content' header
                // @TODO Fallback response handler for NULL results
                $response = $response
                    ->withStatus(StatusCode::NO_CONTENT)
                    ->withBody(new StringStream(""));
            } else {
                // INVALID results: Throw exception
                throw new \RuntimeException("Router: Malformed handler result");
            }
            $buffer = ob_get_clean();
        } catch (\Exception $ex) {
            $buffer = ob_get_clean();
            throw $ex;
        } finally {
            //debug($buffer);
        }

        return $response;
    }
}
