<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 7/8/14
 * Time: 12:56 AM
 */

namespace Flow\Test\Router;

use Flow\Http\Server\ServerRequest;
use Flow\Router\Route;
use Flow\Router\Router;
use Flow\Http\Message\Request;

class RouterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @group match
     */
    public function testMatch()
    {
        $router = new Router();
        $router->connect(new Route('/'));
        $router->connect(new Route('/test'));
        $router->connect(new Route('/test/{foo}'));
        $router->connect(new Route('/test/{foo}/{bar}'));
        $router->connect(new Route('/test/{foo}/{bar}/*'));
        $router->connect(new Route('/test/{bar}-{foo}/{1}-{0}'));

        $route = $router->match(ServerRequest::get('/'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/', $route->getRoute());

        $route = $router->match(ServerRequest::get('/test'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/', $route->getRoute());

        $route = $router->match(ServerRequest::get('/test/bar'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/', $route->getRoute());
        $this->assertEquals($route->getParams('foo'), 'bar');

        $route = $router->match(ServerRequest::get('/test/foo/bar'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/{bar}/', $route->getRoute());
        $this->assertEquals($route->getParams('foo'), 'foo');
        $this->assertEquals($route->getParams('bar'), 'bar');
        $this->assertNull($route->getParams(0));

        $route = $router->match(ServerRequest::get('/test/foo/bar/1'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/{bar}/*/', $route->getRoute());
        $this->assertEquals($route->getParams('foo'), 'foo');
        $this->assertEquals($route->getParams('bar'), 'bar');
        $this->assertEquals($route->getParams(0), 1);

        $route = $router->match(ServerRequest::get('/test/foo/bar/1/two'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/{bar}/*/', $route->getRoute());
        $this->assertEquals($route->getParams('foo'), 'foo');
        $this->assertEquals($route->getParams('bar'), 'bar');
        $this->assertEquals($route->getParams(0), 1);
        $this->assertEquals($route->getParams(1), 'two');

        $route = $router->match(ServerRequest::get('/test/foo-bar/two-1'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{bar}-{foo}/{1}-{0}/', $route->getRoute());
        $this->assertEquals($route->getParams('foo'), 'bar');
        $this->assertEquals($route->getParams('bar'), 'foo');
        $this->assertEquals($route->getParams(0), 1);
        $this->assertEquals($route->getParams(1), 'two');
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
        $router->connect(new Route('/'));
        $router->connect(new Route('/foo'));
        $router->connect(new Route('/foo/bar'));

        $route = $router->match(ServerRequest::get('/prefix/'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/', $route->getRoute());

    }

    /**
     * @group match
     */
    public function testMatchRecursive()
    {
        $this->markTestSkipped('Evaluate recursive matching behavior');

        $routerB = new Router('/test');
        $routerB->connect(new Route('/'))
            ->name('b.root');
        $routerB->connect(new Route('/hello'))
            ->name('b.hello');

        $routerA = new Router();
        $routerA->connect(new Route('/'))
            ->name('a.root');
        $routerA->connect(new Route('/test/**', $routerB))
            ->name('a.router_mount');

        $route = $routerA->match(ServerServerRequest::get('/'));
        $this->assertEquals('a.root', $route->getName());

        $route = $routerA->match(ServerServerRequest::get('/test'));
        $this->assertEquals('b.root', $route->getName());

        $route = $routerA->match(ServerServerRequest::get('/test/hello'));
        $this->assertEquals('b.hello', $route->getName());
    }
}
