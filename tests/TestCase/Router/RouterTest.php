<?php

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
        $router->connect(new Route('/test/{foo}/{bar}/**'));
        $router->connect(new Route('/test/{bar}-{foo}/{1}-{0}'));

        $route = $router->match(ServerRequest::get('/'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/', $route->getRoute());

        $route = $router->match(ServerRequest::get('/test'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test', $route->getRoute());

        $route = $router->match(ServerRequest::get('/test/bar'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}', $route->getRoute());
        $this->assertEquals('bar', $route->getParams('foo'));

        $route = $router->match(ServerRequest::get('/test/foo/bar'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/{bar}', $route->getRoute());
        $this->assertEquals('foo', $route->getParams('foo'));
        $this->assertEquals('bar', $route->getParams('bar'));
        $this->assertNull($route->getParams(0));

        $route = $router->match(ServerRequest::get('/test/foo/bar/1'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/{bar}/*', $route->getRoute());
        $this->assertEquals('foo', $route->getParams('foo'));
        $this->assertEquals('bar', $route->getParams('bar'));
        //$this->assertEquals(1, $route->getParams(0));

        $route = $router->match(ServerRequest::get('/test/foo/bar/1/two'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}/{bar}/**', $route->getRoute());
        $this->assertEquals('foo', $route->getParams('foo'));
        $this->assertEquals('bar', $route->getParams('bar'));
        //$this->assertEquals(1, $route->getParams(0));
        //$this->assertEquals('two', $route->getParams(1));

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
        $route = $router->match(ServerRequest::get('/test/123-myfoo'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/test/{foo}', $route->getRoute());
        $this->assertEquals('123-myfoo', $route->getParams('foo'));
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
        $router = new Router('/myprefix');
        $router->connect(new Route('/'));
        $router->connect(new Route('/foo'));
        $router->connect(new Route('/foo/bar'));

        $route = $router->match(ServerRequest::get('/myprefix/'));
        $this->assertInstanceOf('\Flow\Router\Route', $route);
        $this->assertEquals('/', $route->getRoute());
    }

    /**
     * @group match
     */
    public function testMatchRecursive()
    {
        $this->markTestSkipped('Implement recursive matching behavior');

        $subrouter = new Router();
        $subrouter->connect(new Route('/', ['name' => 'sub.root']));
        $subrouter->connect(new Route('/hello', ['name' => 'sub.hello']));

        $mainrouter = new Router();
        $mainrouter->connect(new Route('/', ['name' => 'main.root']));
        $mainrouter->connect(new Route('/sub/**', ['name' => 'main.submount'], $subrouter));

        $route = $mainrouter->match(ServerRequest::get('/'));
        $this->assertEquals('main.root', $route->getName());

        $route = $mainrouter->match(ServerRequest::get('/sub/test'));
        $this->assertEquals('sub.root', $route->getName());

        $route = $mainrouter->match(ServerRequest::get('/sub/test/hello'));
        $this->assertEquals('sub.hello', $route->getName());
    }
}
