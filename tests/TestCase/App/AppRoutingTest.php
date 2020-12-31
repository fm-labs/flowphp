<?php

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

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->env = Environment::mock();
    }

    /**
     * @return void
     */
    public function testHandleRequest(): void
    {
        $app = new App();
        $app->connect('/', function () {
            $response = new Response(200);
            return $response->withBody(new StringStream('Hello'));
        });
        $app();

        $request = ServerRequest::get('/');
        $response = $app->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals('Hello', $response->getBody());
    }

    /**
     * @return void
     */
    public function testHandleRequestWithStringResult(): void
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

    /**
     * @return void
     */
    public function testHandleRequestWithBufferedContents(): void
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

    /**
     * @return void
     */
    public function testHandleRequestWithBufferedContentAndResponseResult(): void
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

    /**
     * @return void
     */
    public function testAppMiddleware(): void
    {
        $middlewareTest = array();

        $app = new App();
        $app->connect('/', function () use (&$middlewareTest) {
            $middlewareTest[] = 'route';
        });

        $app->before(function () use (&$middlewareTest) {
            $middlewareTest[] = 'before';
        });
        $app->after(function () use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before', 'route', 'after'), $middlewareTest);
    }

    /**
     * @return void
     */
    public function testAppMiddlewareBeforeReturnsResponse(): void
    {
        $middlewareTest = array();

        $app = new App();
        $app->connect('/', function () use (&$middlewareTest) {
            $middlewareTest[] = 'route';
            return (new Response())->withBody(new StringStream('Foo'));
        });

        $app->before(function () use (&$middlewareTest) {
            $middlewareTest[] = 'before';
            return (new Response())->withBody(new StringStream('Bar'));
        });
        $app->after(function () use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $response = $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before'), $middlewareTest);
        $this->assertEquals('Bar', $response->getBody());
    }

    /**
     * @return void
     */
    public function testRouteMiddleware(): void
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

        $app->before(function () use (&$middlewareTest) {
            $middlewareTest[] = 'before';
        });
        $app->after(function () use (&$middlewareTest) {
            $middlewareTest[] = 'after';
        });
        $app();

        $app->handle(ServerRequest::get('/'));

        $this->assertEquals(array('before', 'beforeRoute', 'route', 'afterRoute', 'after'), $middlewareTest);
    }

    /**
     * @group testme
     */
    /**
     * @return void
     */
    public function testRouteMiddlewareBeforeReturnsResponse(): void
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
