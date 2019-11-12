<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 10/30/14
 * Time: 10:53 PM
 */

namespace Flow\Test\App;

use Flow\App\App;
use Flow\Http\Environment;
use Flow\Http\Message\Stream\StringStream;
use Flow\Http\Message\Request;
use Flow\Http\Message\Response;
use Flow\Http\Server\ServerRequest;

class AppRoutingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Environment
     */
    protected $env;

    public function setUp()
    {
        $this->env = Environment::mock();
    }

    public function testHandleRequest()
    {
        $app = new App();
        $app->connect('/', function () {
            $response = new Response( 200);
            return $response->withBody(new StringStream('Hello'));
        });
        $app();

        $request = ServerRequest::get('/');
        $response = $app->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals('Hello', $response->getBody());
    }

    public function testHandleRequestWithStringResult()
    {
        $app = new App();
        $app->connect('/', function () {
            return 'Hello';
        });
        $app();

        $request = ServerRequest::get('/');
        $response = $app->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals('Hello', $response->getBody());
    }

    public function testHandleRequestWithBufferedContents()
    {
        $this->markTestSkipped('Buffered results are not properly implemented yet.');

        $app = new App();
        $app->connect('/', function () {
            echo 'Hello';
        });
        $app();

        $request = ServerRequest::get('/');
        $response = $app->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals('Hello', $response->getBody());
    }

    public function testHandleRequestWithBufferedContentAndResponseResult()
    {
        $this->markTestSkipped('Buffered results are not properly implemented yet.');

        $app = new App();
        $app->connect('/', function () {
            echo "Hi!";
            return (new Response())
                ->withBody(new StringStream('Hello'));
        });
        $app();

        $request = ServerRequest::get('/');
        $response = $app->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals('Hello', $response->getBody());
    }

    public function testAppMiddleware()
    {
        $middlewareTest = array();

        $app = new App();
        $app->connect('/', function () use (&$middlewareTest) {
            $middlewareTest[] = 'route';
        });

        $app->before(function (App $app) use (&$middlewareTest) {
            $middlewareTest[] = 'before';
        });
        $app->after(function (App $app) use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before', 'route', 'after'), $middlewareTest);
    }

    public function testAppMiddlewareBeforeReturnsResponse()
    {
        $middlewareTest = array();

        $app = new App();
        $app->connect('/', function () use (&$middlewareTest) {
            $middlewareTest[] = 'route';
            return (new Response())->withBody(new StringStream('Foo'));
        });

        $app->before(function (App $app) use (&$middlewareTest) {
            $middlewareTest[] = 'before';
            return (new Response())->withBody(new StringStream('Bar'));
        });
        $app->after(function (App $app) use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $response = $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before'), $middlewareTest);
        $this->assertEquals('Bar', $response->getBody());
    }

    public function testRouteMiddleware()
    {
        $middlewareTest = array();

        $app = new App();
        $app->connect('/', function () use (&$middlewareTest) {
            $middlewareTest[] = 'route';
        })
            ->before(function () use (&$middlewareTest) {
                $middlewareTest[] = 'beforeRoute';
            })
            ->after(function () use (&$middlewareTest) {
                $middlewareTest[] = 'afterRoute';
            });

        $app->before(function (App $app) use (&$middlewareTest) {
            $middlewareTest[] = 'before';
        });
        $app->after(function (App $app) use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before', 'beforeRoute', 'route', 'afterRoute', 'after'), $middlewareTest);
    }

    /**
     * @group testme
     */
    public function testRouteMiddlewareBeforeReturnsResponse()
    {
        $middlewareTest = array();

        $app = new App();
        $app->connect('/', function () use (&$middlewareTest) {
            $middlewareTest[] = 'route';
            return (new Response())->withBody(new StringStream('Foo'));
        })
            ->before(function () use (&$middlewareTest) {
                $middlewareTest[] = 'beforeRoute';
                return (new Response())->withBody(new StringStream('Bar'));
            })
            ->after(function () use (&$middlewareTest) {
                $middlewareTest[] = 'afterRoute';
            });

        $app->before(function () use (&$middlewareTest) {
            $middlewareTest[] = 'before';
        });
        $app->after(function () use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $response = $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before', 'beforeRoute', 'after'), $middlewareTest);
        $this->assertEquals('Bar', $response->getBody());
    }
}
