<?php

namespace Flow\Test\Router;

use Flow\Http\Message\Request;
use Flow\Http\Message\Uri;
use Flow\Router\Route;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    protected function mockRequest($method = "GET", $path = "/", $query = [], $data = [])
    {
        $req = (new Request())
            ->withMethod($method)
            ->withUri((new Uri())->withPath($path))
        ;
        return $req;
    }

    /**
     * @group match
     */
    public function testMatchRoot()
    {
        $r = new Route('/');
        $r->setPrefix("");

        $this->assertTrue($r->match($this->mockRequest("GET", "/")));
    }

    /**
     * @group match
     */
    public function testMatchStatic()
    {
        $r = new Route('/foo');

        $this->assertTrue($r->match($this->mockRequest("GET", "/foo")));
        $this->assertTrue($r->match($this->mockRequest("GET", "/foo/")));
        $this->assertFalse($r->match($this->mockRequest("GET", "/foo/bar")));

        $r = new Route('/foo/bar');
        $this->assertTrue($r->match($this->mockRequest("GET", "/foo/bar")));
        $this->assertTrue($r->match($this->mockRequest("GET", "/foo/bar/")));
        $this->assertFalse($r->match($this->mockRequest("GET", "/foo")));
    }

    /**
     * @group match
     */
    public function testMatchWithNamedParams()
    {
        $r = new Route('/{controller}/');
        $req = $this->mockRequest("GET", "/foo");
        $this->assertTrue($r->match($req));
        $this->assertEquals(['controller' => 'foo'], $r->getParams());

        $r = new Route('/{controller}/{action}/');
        $this->assertTrue($r->match($this->mockRequest("GET", "/foo/edit")));
        $this->assertEquals(['controller' => 'foo', 'action' => 'edit'], $r->getParams());
    }
    /**
     * @group match
     * @group matchOptional
     */
    public function testMatchWithOptionalNamedParams()
    {
        $this->markTestIncomplete();
        $r = new Route('/foo/{?optional}');
        $req = $this->mockRequest("GET", "/foo");
        $this->assertTrue($r->match($req));
        $this->assertEquals([], $r->getParams());

        $req = $this->mockRequest("GET", "/foo/bar");
        $this->assertTrue($r->match($req));
        $this->assertEquals(['optional' => 'bar'], $r->getParams());

        $r = new Route('/foo/{?optional}/asdf');
        $req = $this->mockRequest("GET", "/foo/asdf");
        $this->assertTrue($r->match($req));
        $this->assertEquals(['optional' => 'asdf'], $r->getParams());

        $req = $this->mockRequest("GET", "/foo/bar/asdf");
        $this->assertTrue($r->match($req));
        $this->assertEquals(['optional' => 'bar'], $r->getParams());
    }

    /**
     * @group match
     * @group wildcard
     */
    public function testMatchWithNonGreedyWildcardParams()
    {
        $r = new Route('/foo/*');
        $req = $this->mockRequest("GET", "/foo");
        $this->assertFalse($r->match($req));

        $req = $this->mockRequest("GET", "/foo/bar");
        $this->assertTrue($r->match($req));
        //$this->assertEquals(['*' => 'bar'], $r->getParams());

        $req = $this->mockRequest("GET", "/foo/bar/asdf");
        $this->assertFalse($r->match($req));

        $r = new Route('/foo/*/more');
        $req = $this->mockRequest("GET", "/foo");
        $this->assertFalse($r->match($req));
        $req = $this->mockRequest("GET", "/foo/bar");
        $this->assertFalse($r->match($req));
        $req = $this->mockRequest("GET", "/foo/bar/more");
        $this->assertTrue($r->match($req));
        $this->assertEquals([0 => 'bar'], $r->getParams());
    }

    /**
     * @group match
     * @group wildcard
     */
    public function testMatchWithGreedyWildcardParams()
    {
        $r = new Route('/foo/**');
        $req = $this->mockRequest("GET", "/foo");
        $this->assertFalse($r->match($req));

        $req = $this->mockRequest("GET", "/foo/bar");
        $this->assertTrue($r->match($req));
        $this->assertEquals([0 => 'bar'], $r->getParams());

        $req = $this->mockRequest("GET", "/foo/bar/has/another/subpath");
        $this->assertTrue($r->match($req));
        $this->assertEquals([0 => 'bar/has/another/subpath'], $r->getParams());

        $r = new Route('/foo/**/bar');
        $req = $this->mockRequest("GET", "/foo/has/another/subpath/at/bar");
        $this->assertTrue($r->match($req));
        $this->assertEquals([0 => 'has/another/subpath/at'], $r->getParams());
    }

    public function testMatchWithNamedGreedyWildcardParams()
    {
        $this->markTestSkipped('Named greedy wildcard parameters is not implemented yet');
    }

    /**
     * @group match
     */
    public function testMatchWithIndexedParams()
    {
        $r = new Route('/{controller}/{1}/{0}');
        $req = $this->mockRequest("GET", "/foo/a/b");
        $this->assertTrue($r->match($req));
        $this->assertEquals(['controller' => 'foo', '0' => "b", '1' => "a"], $r->getParams());
    }

    /**
     * @group match
     */
    public function testMatchWithCustomRegexPatterns()
    {
        $r = new Route('/{controller}/{action}/{0}/*', array(
            'patterns' => array(
                'controller' => 'test\-[\w]+',
                0 => '[\d]+',
            )
        ));
        $req = $this->mockRequest("GET", "/foo/index/123/c");
        $this->assertFalse($r->match($req));

        $req = $this->mockRequest("GET", "/test-me/index/123/c");
        $this->assertTrue($r->match($req));
        $this->assertEquals(['controller' => 'test-me', 'action' => "index", 0 => 123, 1 => "c"], $r->getParams());
    }


    public function testMatchPrefixed()
    {
        $r = new Route('/', ['prefix' => '/mytest']);
        $this->assertTrue($r->match($this->mockRequest("GET", "/mytest/")));
        $this->assertTrue($r->match($this->mockRequest("GET", "/mytest")));

        $r = new Route('/foo/{controller}/{action}', ['prefix' => '/mytest']);
        $this->assertTrue($r->match($this->mockRequest("GET", "/mytest/foo/mycontroller/myaction")));
        $this->assertEquals(['controller' => 'mycontroller', 'action' => 'myaction'], $r->getParams());
    }
}
