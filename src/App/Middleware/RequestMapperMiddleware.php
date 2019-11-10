<?php
namespace Flow\App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flow\App\Middleware;
use Flow\Flow;

class RequestMapperMiddleware extends Middleware
{
    protected $request;

    protected $map = [
        'plain' => [
            'mime' => [
                'text/plain', 'text/text'
            ],
            'headers' => [
                'Content-Type' => 'text/plain'
            ],
            //'defaultView' => '\Flow\View\TextView',
        ],
        'html' => [
            'mime' => [
                'text/html', 'application/xhtml+xml'/*, 'application/xml'*/
            ],
            'headers' => [
               'Content-Type' => 'text/html'
            ],
            //'defaultView' => '\Flow\View\HtmlView',
        ],
        'json' => [
            'mime' => [
               'text/json', 'application/json'
            ],
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8'
            ],
            //'defaultView' => '\Flow\View\JsonView',
        ],
        'xml' => [
            'mime' => [
                'application/xml'
            ],
            'headers' => [
                'Content-Type' => 'application/xml'
            ],
            //'defaultView' => '\Flow\View\XmlView',
        ],
        'stream' => [
            'mime' => [
                'application/octet-stream', 'application/*'
            ],
            'headers' => [
                'Content-Type' => 'application/octet-stream'
            ],
            //'defaultView' => '\Flow\View\StreamView',
        ]
    ];


    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->app->response;

        // Check 'Accept' header
        // and apply headers according to type map
        $accepts = $this->accepts($request);
        if ($accepts) {
            $type = ($this->findType($accepts)) ?: 'plain';
            $headers = $this->map[$type]['headers'] ?? [];
            foreach ($headers as $k => $v) {
                $response = $response->withHeader($k, $v);
            }
        }

        // Inject X-Powered-By header
        // @todo remove
        $response = $response
            ->withHeader('X-Powered-By', Flow::name());

        // Set type info in request attributes
        //$request
        //    ->withAttribute('@type', $type);

        $this->app->response = $response;

        return $handler->handle($request);
    }

    public function is($check)
    {
        $request = $this->request;

        switch(strtolower($check)) {
            case "get":
                return ($request->getMethod() == "GET") ? true : false;
            case "post":
                return ($request->getMethod() == "POST") ? true : false;
            case "put":
                return ($request->getMethod() == "PUT") ? true : false;
            case "delete":
                return ($request->getMethod() == "DELETE") ? true : false;
            case "options":
                return ($request->getMethod() == "OPTIONS") ? true : false;
            case "head":
                return ($request->getMethod() == "HEAD") ? true : false;
            /*
            case "ssl":
                return (isset($this->env['HTTPS'])) ? (bool) $this->env['HTTPS'] : false;
            case "ajax":
                return (isset($this->env['HTTP_X_REQUESTED_WITH']) && $this->env['HTTP_X_REQUESTED_WITH'] == 'XmlHttpRequest')
                    ? true : false;
            case "json":
                return (isset($this->env['CONTENT_TYPE']) && $this->env['CONTENT_TYPE'] == "application/json");
            case "form":
                return (isset($this->env['CONTENT_TYPE']) && $this->env['CONTENT_TYPE'] == "application/x-www-form-urlencoded");
            default:
                throw new \Exception('Unknown request detector ' . $check);
            */
        }

        return false;
    }

    protected function accepts(ServerRequestInterface $request)
    {
        $mime = "";
        $accept = $request->getHeaderLine('Accept');
        if ($accept) {
            $parts = explode(";", $accept);
            $mime = explode(',', $parts[0]);
        }

        return $mime;
    }

    protected function findType($mime)
    {
        foreach ((array)$mime as $_mime) {
            foreach ($this->map as $type => $config) {
                $typeMime = $config['mime'] ?? [];
                foreach ($typeMime as $_typeMime) {
                    if ($this->matchMime($_mime, $_typeMime)) {
                        return $type;
                    }
                }
            }
        }
    }

    protected function matchMime($needle, $format, $strict = false)
    {
        list($cat, $type) = explode("/", $needle);
        list($fcat, $ftype) = explode("/", $format);

        if ($strict && $cat == $fcat && $type == $ftype) {
            return true;
        }

        if (($fcat == "*" || $fcat == $cat) && ($ftype == "*" || $ftype == $type)) {
            return true;
        }

        return false;
    }

}