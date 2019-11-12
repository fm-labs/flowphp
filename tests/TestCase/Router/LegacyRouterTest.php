<?php

namespace Flow\Test\Router;

use Flow\Http\Server\ServerRequest;
use Flow\Router\LegacyRoute;
use Flow\Router\Router;
use Flow\Http\Message\Request;

class LegacyRouterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @group match
     */
    public function testMatch()
    {
        $router = new Router();
        $router->connect(new LegacyRoute('/'));
        $router->connect(new LegacyRoute('/test'));
        $router->connect(new LegacyRoute('/test/{foo}'));
        $router->connect(new LegacyRoute('/test/{foo}/{bar}'));
        $router->connect(new LegacyRoute('/test/{foo}/{bar}/*'));
        //$router->connect(new Route('/test/{bar}-{foo}/{1}-{0}'));

        $route = $router->match(ServerRequest::get('/'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/', $route->getRoute());

        $route = $router->match(ServerRequest::get('/test'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/test/', $route->getRoute());

        $route = $router->match(ServerRequest::get('/test/bar'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/test/{foo}/', $route->getRoute());
        $this->assertEquals('bar', $route->getParams('foo'));

        $route = $router->match(ServerRequest::get('/test/foo/bar'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/test/{foo}/{bar}/', $route->getRoute());
        $this->assertEquals('foo', $route->getParams('foo'));
        $this->assertEquals('bar', $route->getParams('bar'));
        $this->assertNull($route->getParams(0));

        $route = $router->match(ServerRequest::get('/test/foo/bar/1'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/test/{foo}/{bar}/*/', $route->getRoute());
        $this->assertEquals('foo', $route->getParams('foo'));
        $this->assertEquals('bar', $route->getParams('bar'));
        $this->assertEquals(1, $route->getParams(0));

        $route = $router->match(ServerRequest::get('/test/foo/bar/1/two'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/test/{foo}/{bar}/*/', $route->getRoute());
        $this->assertEquals('foo', $route->getParams('foo'));
        $this->assertEquals('bar', $route->getParams('bar'));
        $this->assertEquals(1, $route->getParams(0));
        $this->assertEquals('two', $route->getParams(1));

        /*
        $route = $router->match(ServerRequest::get('/test/foo-bar/two-1'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{bar}-{foo}/{1}-{0}/', $route->getRoute());
        $this->assertEquals('bar', $route->getParams('foo'));
        $this->assertEquals('foo', $route->getParams('bar'));
        $this->assertEquals(1, $route->getParams(0));
        $this->assertEquals('two', $route->getParams(1));
        */


        // match with special chars
        $route = $router->match(ServerRequest::get('/test/123-~myfoo'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/test/{foo}/', $route->getRoute());
        $this->assertEquals('123-~myfoo', $route->getParams('foo'));
    }

    /**
     * @group match
     */
    public function testMatchNonExistent()
    {
        $router = new Router();
        $this->assertNull($router->match(ServerRequest::get('/does-not-exist')));
    }

    /**
     * @group match
     */
    public function testMatchWithPrefix()
    {
        $router = new Router('/prefix');
        $router->connect(new LegacyRoute('/'));
        $router->connect(new LegacyRoute('/foo'));
        $router->connect(new LegacyRoute('/foo/bar'));

        $route = $router->match(ServerRequest::get('/prefix/'));
        $this->assertInstanceOf('\Flow\Router\LegacyRoute', $route);
        $this->assertEquals('/', $route->getRoute());

    }

    /**
     * @group match
     */
    public function testMatchRecursive()
    {
        $this->markTestSkipped('Evaluate recursive matching behavior');

        $routerB = new Router('/test');
        $routerB->connect(new LegacyRoute('/'))
            ->name('b.root');
        $routerB->connect(new LegacyRoute('/hello'))
            ->name('b.hello');

        $routerA = new Router();
        $routerA->connect(new LegacyRoute('/'))
            ->name('a.root');
        $routerA->connect(new LegacyRoute('/test/**', $routerB))
            ->name('a.router_mount');

        $route = $routerA->match(ServerServerRequest::get('/'));
        $this->assertEquals('a.root', $route->getName());

        $route = $routerA->match(ServerServerRequest::get('/test'));
        $this->assertEquals('b.root', $route->getName());

        $route = $routerA->match(ServerServerRequest::get('/test/hello'));
        $this->assertEquals('b.hello', $route->getName());
    }
}
