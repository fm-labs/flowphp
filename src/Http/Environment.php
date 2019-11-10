<?php

namespace Flow\Http;

use FmLabs\Uri\Uri;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Flow\Http\Message\Stream\StringStream;
use Flow\Http\Server\ServerRequest;

/**
 * Flow Environment
 *
 *
 * General:
 * ------------
 * DOCUMENT_ROOT:
 *      Absolute path to webserver's document root
 * SCRIPT_FILENAME:
 *      Absolute path to executing script filename on disk
 * SCRIPT_NAME:
 *      Path to executing script filename relative to DOCUMENT_ROOT
 * PHP_SELF:
 *      Same as SCRIPT_NAME
 * REQUEST_URI:
 *      Url requested by client (including the query string, but without script filename).
 *      Relative to base url (host root url)
 * QUERY_STRING:
 *      Query string without '?'
 *
 *
 * Flow specific env settings
 * -------------
 * SLIKK_APPDIR:
 *      Absolute path to app
 * SLIKK_APPURL:
 *      Relative uri to app
 * SLIKK_HTTP_METHOD_OVERRIDE_ORIGINAL:
 *      Holds the original request method, if HTTP_X_HTTP_METHOD_OVERRIDE is set
 *
 * Webserver specific
 * -------------
 * a) Nginx
 * b) Apache, htaccess, mod_rewrite
 * c) PHP-FPM, cgi.fix_pathinfo
 * d) IIS
 * e) Non-SAPI
 *
 * @TODO Remove dead code
 */
class Environment implements ServerRequestFactoryInterface, \ArrayAccess
{
    /**
     * @var array List of environment variables
     */
    protected $SERVER;

    protected $GET;

    protected $POST;

    protected $COOKIE;

    protected $FILES;

    static public function fromGlobals()
    {
        return new self($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
    }

    static public function fromArray(array $env)
    {
        return new self($env);
    }

    /**
     * Mock a GET request
     *
     * @param $path
     * @param array $server
     * @return ServerRequestInterface
     */
    public static function getRequest($path, $server = [])
    {
        $server['PATH_INFO'] = $path;
        $server['REQUEST_METHOD'] = "GET";

        $env = new self($server);
        return $env->createServerRequest("POST", new Uri($path), $server);
    }

    /**
     * Mock a POST request
     *
     * @param $path
     * @param array $data
     * @param array $server
     * @return ServerRequestInterface
     */
    public static function postRequest($path, $data = [], $server = [])
    {
        $server['PATH_INFO'] = $path;
        $server['REQUEST_METHOD'] = "POST";

        $env = new self($server, [], $data);
        return $env->createServerRequest("POST", new Uri($path), $server);
    }
    
    /**
     * Only for testing purposes.
     *
     * @param array $env
     * @return Environment
     * @throws \Exception
     * @deprecated Use Environment::fromArray() instead
     * @todo Remove from production code
     */
    static public function mock($env = array())
    {
        $env = array_merge(array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'Flow Agent',
            'REMOTE_ADDR' => '127.0.0.1',
            'PATH_INFO' => '/',
            'SCRIPT_NAME' => '',
            'QUERY_STRING' => '',
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
        ), $env);

        return new self($env);
    }

    public function __construct($server = [], $get = [], $post = [], $cookies = [], $files = [])
    {
        /*
        // context doc root as doc root
        if (isset($server['CONTEXT_DOCUMENT_ROOT'])) {
            $server['DOCUMENT_ROOT'] = $server['CONTEXT_DOCUMENT_ROOT'];
        }

        // strip off query string from request uri
        if (isset($server['REQUEST_URI'])) {
            $server['REQUEST_URI_NOQUERY'] = $server['REQUEST_URI'];
            if (strpos($server['REQUEST_URI'], '?') > 0) {
                $server['REQUEST_URI_NOQUERY'] = substr(
                    $server['REQUEST_URI'],
                    0,
                    strpos($server['REQUEST_URI'], '?') - strlen($server['REQUEST_URI'])
                );
            }
        } else {
            $server['REQUEST_URI'] = $server['REQUEST_URI_NOQUERY'] = '/';
        }

        // base dir guessing
        if (!isset($server['SLIKK_APPDIR'])) {
            $server['SLIKK_APPDIR'] = dirname($server['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR;
        }

        // base url guessing
        if (!isset($server['SLIKK_APPURL'])) {
            $server['SLIKK_APPURL'] = substr($server['SCRIPT_NAME'], 0, -strlen(basename($server['SCRIPT_NAME'])));
        }

        // check base url against request uri
        if (strpos($server['REQUEST_URI_NOQUERY'], $server['SLIKK_APPURL']) < 0) {
            throw new \Exception('App base url misconfigured');
        }

        // get path info from REQUEST_URI
        if (!isset($server['PATH_INFO'])) {
            // strip off base path
            $pathInfo = substr($server['REQUEST_URI_NOQUERY'], strlen($server['SLIKK_APPURL']) - 1);

            if (preg_match('/(.*)' . basename($server['SCRIPT_NAME']) . '$/', $pathInfo, $matches)) {
                $pathInfo = $matches[1];
            }

            $server['PATH_INFO'] = $pathInfo;
            //debug('PATH_INFO: ' . $server['PATH_INFO']);
        }

        // method override
        if (isset($server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $server['SLIKK_HTTP_METHOD_OVERRIDE_ORIGINAL'] = $server['REQUEST_METHOD'];
            $server['REQUEST_METHOD'] = $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
        }
        */

        $this->SERVER = $server;
        $this->GET = $get;
        $this->POST = $post;
        $this->COOKIE = $cookies;
        $this->FILES = $files;
    }

    /**
     * @param $key
     * @return string|null
     */
    public function get($key)
    {
        if (isset($this->SERVER[$key])) {
            return $this->SERVER[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->SERVER;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->SERVER[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        if (isset($this->SERVER[$offset])) {
            unset($this->SERVER[$offset]);
        }
    }

    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing
     * of the given values is performed, and, in particular, no attempt is made to
     * determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     * @param array $serverParams Array of SAPI parameters with which to seed
     *     the generated request instance.
     *
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
//$_REQUEST['query']	test
//$_GET['query']	test
//$_SERVER['DOCUMENT_ROOT']	/home/flow/workspaces/php/flowphp-examples/src
//$_SERVER['REMOTE_ADDR']	127.0.0.1
//$_SERVER['REMOTE_PORT']	56790
//$_SERVER['SERVER_SOFTWARE']	PHP 7.1.23-4+ubuntu14.04.1+deb.sury.org+1 Development Server
//$_SERVER['SERVER_PROTOCOL']	HTTP/1.1
//$_SERVER['SERVER_NAME']	localhost
//$_SERVER['SERVER_PORT']	9081
//$_SERVER['REQUEST_URI']	/000-phpinfo/index.php?query=test
//$_SERVER['REQUEST_METHOD']	GET
//$_SERVER['SCRIPT_NAME']	/000-phpinfo/index.php
//$_SERVER['SCRIPT_FILENAME']	/home/flow/workspaces/php/flowphp-examples/src/000-phpinfo/index.php
//$_SERVER['PHP_SELF']	/000-phpinfo/index.php
//$_SERVER['QUERY_STRING']	query=test
//$_SERVER['HTTP_HOST']	localhost:9081
//$_SERVER['HTTP_USER_AGENT']	Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0
//$_SERVER['HTTP_ACCEPT']	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
//$_SERVER['HTTP_ACCEPT_LANGUAGE']	en,en-US;q=0.5
//$_SERVER['HTTP_ACCEPT_ENCODING']	gzip, deflate
//$_SERVER['HTTP_DNT']	1
//$_SERVER['HTTP_CONNECTION']	keep-alive
//$_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']	1
//$_SERVER['REQUEST_TIME_FLOAT']	1573181876.2998
//$_SERVER['REQUEST_TIME']	1573181876

        // headers
        $headers = $this->parseHeaders();
        //debug($headers);
        //debug(getallheaders());

        // protocol
        $protocol = $this->SERVER['SERVER_PROTOCOL'] ?? "HTTP/1.1";
        //list($scheme, $protocolVersion) = explode("/", $protocol); // alternative-1
        //$protocolVersion = (preg_match("/^(.*)\/(.*)$/", $protocol, $matches)) ? $matches[2] : "1.1"; // alternative-2
        if (preg_match("/^(.*)\/(.*)$/", $protocol, $matches)) {
            $protocolVersion = $matches[2];
        } else {
            $protocolVersion = "1.1";
        }

        // method
        $method = $this->SERVER['REQUEST_METHOD'] ?? "GET"; // @TODO Handle requests without request method

        // scheme
        $scheme = (isset($this->SERVER['HTTPS']) && $this->SERVER['HTTPS'] == 'on') ? 'https' : 'http';

        // host
        $host = $this->SERVER['HTTP_HOST'] ?? ""; // @TODO Handle requests without host header
        $port = null;
        $hostParts = explode(":", $host);
        if (count($hostParts) == 2) {
            list($host, $port) = $hostParts;
        }

        $path = $this->SERVER['PATH_INFO'] ?? null;
        $query = $this->SERVER['QUERY_STRING'] ?? null;

        if (!$path && isset($this->SERVER['REQUEST_URI'])) {
            $pathWithQuery = $this->SERVER['REQUEST_URI'];
            // strip the script name
            $scriptName = $this->SERVER['SCRIPT_NAME'] ?? "";
            if (substr($pathWithQuery, 0, strlen($scriptName)) == $scriptName) {
                $pathWithQuery = substr($pathWithQuery, strlen($scriptName));
                $pathParts = explode("?", $pathWithQuery);
                if (count($pathParts) == 1) {
                    list($path) = $pathParts;
                } elseif (count($pathParts) == 2) {
                    list($path, $query) = $pathParts;
                } else {
                    // More than one '?' in the URI .. something must be wrong
                }
            }
        }

        // relative path including query string
        /*
        $pathWithQuery = "";
        if (isset($this->SERVER['REQUEST_URI'])) {
        } elseif (isset($this->SERVER['PATH_INFO'])) {
            $pathWithQuery = $this->SERVER['PATH_INFO'];
            if (isset($this->SERVER['QUERY_STRING'])) {
                $pathWithQuery .= '?' . $this->SERVER['QUERY_STRING'];
            }
        }
        */

        // uri
        $uri = $uri
            ->withScheme($scheme)
            //->withUserInfo()
            ->withHost($host)
            ->withPort($port)
            ->withQuery($query)
            ->withPath($path)
            ->withFragment("") // not in a request
        ;

        // request target
        // https://tools.ietf.org/html/rfc7230#section-5.3
        // - origin-form = absolute-path [ "?" query ] (All requests except CONNECT and OPTIONS)
        // - absolute-form = absolute-path [ "?" query ] (All proxy requests except CONNECT and OPTIONS)
        // - authority-form = authority (Authority = [host][:port], without user info) (All CONNECT requests)
        // - asteriks-form = "*" (All OPTIONS requests)
        switch ($method) {
            case "CONNECT":
                $target = $uri->getAuthority();
                break;
            case "OPTIONS":
                $target = "*";
                break;
            default:
                $pathWithQuery = join("?", [$path, $query]);
                $target = $pathWithQuery;
                break;
        }

        // query params
        $queryParams = $uri->getQueryData();

        // @TODO cookie params
        $cookieParams = [];

        // @TODO uploaded files
        $uploadedFiles = [];

        // @TODO attributes
        $attributes = [];

        // body
        //$body = new FileStream("php://input");
        if (in_array($method, ["POST", "PUT", "PATCH", "DELETE"])) {
            $input = file_get_contents("php://input");
        } else {
            $input = "";
        }
        $body = new StringStream($input);

        // parsed body
        // @TODO Parse request body

        // build request
        $request = new ServerRequest();
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }
        foreach ($attributes as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        return $request
            ->withProtocolVersion($protocolVersion)
            ->withMethod($method)
            ->withRequestTarget($target)
            ->withUri($uri)
            ->withQueryParams($queryParams)
            //->withCookieParams($cookieParams)
            //->withUploadedFiles($uploadedFiles)
            ->withBody($body)
            //->withParsedBody($parsed)
        ;
    }

    protected function parseHeaders()
    {
        $headers = [];

        //if (function_exists('getallheaders')) {
        //    foreach (getallheaders() as $name => $value) {
        //        $headers[$name] = $value;
        //    }
        //} else {
        foreach ($this->SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } elseif ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } elseif ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }
        //}

        return $headers;
    }

    /*** HELPER METHODS ***/

    /**
     * @return string
     * @deprecated
     */
    public function getOriginalMethod()
    {
        return (isset($this->SERVER['SLIKK_HTTP_METHOD_OVERRIDE_ORIGINAL']))
            ? $this->SERVER['SLIKK_HTTP_METHOD_OVERRIDE_ORIGINAL']
            : $this->getMethod();
    }

    /**
     * @return string
     * @deprecated
     */
    public function getMethod()
    {
        return $this->SERVER['REQUEST_METHOD'] ?? "";
    }

    /**
     * @return string
     * @deprecated
     */
    public function getContentType()
    {
        return $this->SERVER['CONTENT_TYPE'] ?? "";
    }

    /**
     * @return string
     * @deprecated
     */
    public function getUserAgent()
    {
        return $this->SERVER['HTTP_USER_AGENT'] ?? "";
    }

    /**
     * @return string
     * @deprecated
     */
    public function getScheme()
    {
        return (isset($this->SERVER['HTTPS']) && $this->SERVER['HTTPS'] == 'on') ? 'https' : 'http';
    }

    /**
     * @return string
     * @deprecated
     */
    public function getHost()
    {
        return $this->SERVER['SERVER_NAME'] ?? "";
    }

    /**
     * @return string
     * @deprecated
     */
    public function getHostUrl()
    {
        return $this->getScheme() . '://' . $this->getHost();
    }

    /**
     * @return string
     * @deprecated
     */
    public function getPort()
    {
        return $this->SERVER['SERVER_PORT'] ?? "";
    }

    /**
     * @return string
     * @deprecated
     */
    public function getRemoteIp()
    {
        return $this->SERVER['REMOTE_ADDR'] ?? "";
    }

    /**
     * @return string
     * @deprecated
     */
    public function getReferer()
    {
        return $this->getReferrer();
    }

    /**
     * @return string
     * @deprecated
     */
    public function getReferrer()
    {
        //@todo
        return (isset($this->SERVER['HTTP_REFERER'])) ? $this->SERVER['HTTP_REFERER'] : null;

        if (isset($this->SERVER['REDIRECT_URL'])) {
            return substr($this->SERVER['REDIRECT_URL'], strlen($this->SERVER['SLIKK_APPURL']) - 1);
        }

        return null;
    }

    /**
     * @return string
     * @deprecated
     */
    public function getPath()
    {
        return $this->SERVER['PATH_INFO'] ?? "";
    }




    /*
    public function getInput()
    {
        if ($this->input === null) {
            $this->parseInput();
        }
        return $this->input;
    }

    protected function parseQuery($query = null)
    {
        if (is_null($query)) {
            $query = $_GET;
        }

        //@todo check
        if (ini_get('magic_quotes_gpc') === '1') {
            $query = stripslashes_deep($query);
        }

        if ($this->SERVER['QUERY_STRING']) {
            parse_str($this->SERVER['QUERY_STRING'], $query);
        }

        $this->query = $query;
    }
    protected function parseData($data = null)
    {

        if (!is_null($data)) {
            return $data;

        } elseif ($_POST) {
            return $_POST;

        } elseif ($this->is('json')) {
            return json_decode($this->getInput(), true);

        } elseif (($this->is('put') || $this->is('delete')) && $this->is('form')) {
            $data = $this->getInput();
            //@todo multibyte
            parse_str($data, $this->data);
        }

        if (ini_get('magic_quotes_gpc') === '1') {
            $this->data = stripslashes_deep($this->data);
        }
    }

    protected function parseFiles($files = null)
    {
        if (is_null($files)) {
            $files = $_FILES;
        }
        $this->files = $files;
    }
    */
}
