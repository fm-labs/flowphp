<?php

namespace Flow\Test\Router;

use Flow\Router\Route;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $r = new Route('/');
        $this->assertEquals('/', $r->getRoute());

        $r = new Route('/', array(
            'defaults' => array('foo' => 'bar'),
            'pass' => array('foo'),
        ));
    }

    /**
     * @group describe
     */
    public function testDescribe()
    {
        $r = new Route('/');
        $r->describe();
        $this->assertEquals(array(), $r->schema);

        $r = new Route('/{controller}/{action}');
        $r->describe();
        $expected = array(
            array(
                'type' => 'named',
                'name' => 'controller',
                'pattern' => '([\w]+)',
                'optional' => false
            ),
            array(
                'type' => 'named',
                'name' => 'action',
                'pattern' => '([\w]+)',
                'optional' => false
            )
        );
        $this->assertEquals($expected, $r->schema);

        $r = new Route('/{foo}-{bar}/{action}/*');
        $r->describe();
        $expected = array(
            array(
                'type' => 'named',
                'name' => 'foo',
                'pattern' => '([\w]+)',
                'optional' => false
            ),
            array(
                'type' => 'named',
                'name' => 'bar',
                'pattern' => '([\w]+)',
                'optional' => false
            ),
            array(
                'type' => 'named',
                'name' => 'action',
                'pattern' => '([\w]+)',
                'optional' => false
            ),
            array(
                'type' => 'wildcard',
                'mode' => 'non-greedy',
                'pattern' => '(.+)/'
            )
        );
        $this->assertEquals($expected, $r->schema);

        $r = new Route('/foo/{?maybe}');
        $r->describe();
        $expected = array(
            array(
                'type' => 'named',
                'name' => 'maybe',
                'pattern' => '([\w]+)?',
                'optional' => true
            ),
        );
        $this->assertEquals($expected, $r->schema);
    }

    /**
     * @group match
     */
    public function testMatchPath()
    {
        $r = new Route('/');
        $this->assertTrue($r->matchPath('/'));

        $r = new Route('/foo');
        $this->assertTrue($r->matchPath('/foo'));

        $r = new Route('/foo/bar');
        $this->assertTrue($r->matchPath('/foo/bar'));
    }

    /**
     * @group match
     */
    public function testMatchPathWithPrefix()
    {
        $r = new Route('/', array('prefix' => '/test'));
        $this->assertTrue($r->matchPath('/test/'));

        $r = new Route('/foo', array('prefix' => '/test'));
        $this->assertTrue($r->matchPath('/test/foo'));

        $r = new Route('/foo/bar', array('prefix' => '/test'));
        $this->assertTrue($r->matchPath('/test/foo/bar'));
    }


    /**
     * @group match
     */
    public function testMatchRoot()
    {
        $r = new Route('/');
        // compile
        //$r->compile();
        //$this->assertEquals('\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/"));
    }

    /**
     * @group match
     */
    public function testMatchStatic()
    {
        $r = new Route('/foo');
        // compile
        //$r->compile();
        //$this->assertEquals('\/foo\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo/"));


        $r = new Route('/foo/bar');
        // compile
        //$r->compile();
        //$this->assertEquals('\/foo\/bar\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo/bar/"));
        $this->assertFalse($r->matchPath("/foo/"));
        $this->assertFalse($r->matchPath("/foo/bar/bla/"));
    }

    /**
     * @group match
     */
    public function testMatchWithNamedParams()
    {
        $r = new Route('/{controller}/');
        // compile
        //$r->compile();
        //$this->assertEquals('\/([\w]+)\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo/"));
        $this->assertEquals($r->getParams('controller'), 'foo');

        $r = new Route('/{controller}/{action}/');
        // compile
        //$r->compile();
        //$this->assertEquals('\/([\w]+)\/([\w]+)\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo/index"));
        $this->assertEquals($r->getParams('controller'), 'foo');
        $this->assertEquals($r->getParams('action'), 'index');

        $this->assertFalse($r->matchPath('/foo/bar/1'));
        $this->assertFalse($r->matchPath('/foo/bar/test'));
    }
    /**
     * @group match
     * @group matchOptional
     */
    public function testMatchWithOptionalNamedParams()
    {
        $r = new Route('/foo/{?optional}');
        // compile
        //$r->compile();
        //$this->assertEquals('\/foo\/([\w]+)?\/?', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo"));
        $this->assertTrue($r->matchPath("/foo/bar"));
        $this->assertEquals($r->getParams('optional'), 'bar');
    }

    /**
     * @group match
     * @group wildcard
     */
    public function testMatchWithNonGreedyWildcardParams()
    {
        $r = new Route('/foo/*');
        // compile
        //$r->compile();
        //$this->assertEquals('\/foo\/(.+)\/', $r->getCompiled());

        // match
        $this->assertFalse($r->matchPath('/foo'));

        $this->assertTrue($r->matchPath("/foo/bar"));
        $this->assertEquals($r->getParams(0), 'bar');

        $this->assertTrue($r->matchPath("/foo/0"));
        $this->assertEquals($r->getParams(0), 0);

        $this->assertTrue($r->matchPath("/foo/bar/hello"));
        $this->assertEquals($r->getParams(0), 'bar');
        $this->assertEquals($r->getParams(1), 'hello');

        $r = new Route('/{controller}/*');
        // compile
        //$r->compile();
        //$this->assertEquals('\/([\w]+)\/(.+)\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo/index"));
        $this->assertEquals($r->getParams('controller'), 'foo');
        $this->assertEquals($r->getParams(0), 'index');

        $this->assertTrue($r->matchPath("/foo/index/action"));
        $this->assertEquals($r->getParams('controller'), 'foo');
        $this->assertEquals($r->getParams(0), 'index');
        $this->assertEquals($r->getParams(1), 'action');
    }

    /**
     * @group match
     * @group wildcard
     */
    public function testMatchWithGreedyWildcardParams()
    {
        $r = new Route('/foo/**');
        // compile
        //$r->compile();
        //$this->assertEquals('\/foo\/(.*)', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath('/foo'));

        $this->assertTrue($r->matchPath("/foo/bar/test/bla/"));
        $this->assertEquals($r->getParams(0), 'bar/test/bla/');


        // more complex wildcard route
        $this->markTestSkipped('Complex wildcard routes are not implemented yet');

        $r = new Route('/foo/**/bar');
        $this->assertFalse($r->matchPath('/foo'));

        $this->assertTrue($r->matchPath("/foo/test/bar/"));
        $this->assertEquals($r->getParams(0), 'test/');

        $this->assertTrue($r->matchPath("/foo/test/bla/bar/"));
        $this->assertEquals($r->getParams(0), 'test/bla/');


    }

    public function testMatchWithNamedGreedyWildcardParams()
    {
        $this->markTestSkipped('Named greedy wildcard parameters is not implemented yet');
    }

    /**
     * @group match
     */
    public function testMatchWithOrderedParams()
    {
        $r = new Route('/{controller}/{1}/{0}');
        // compile
        //$r->compile();
        //$this->assertEquals('\/([\w]+)\/([\w]+)\/([\w]+)\/', $r->getCompiled());

        // match
        $this->assertTrue($r->matchPath("/foo/index/bar"));
        $this->assertEquals($r->getParams('controller'), 'foo');
        $this->assertEquals($r->getParams(0), 'bar');
        $this->assertEquals($r->getParams(1), 'index');
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
        // compile
        //$r->compile();
        //$this->assertEquals('\/(test\-[\w]+)\/([\w]+)\/([\d]+)\/(.+)\/', $r->getCompiled());

        $this->assertFalse($r->matchPath('/foo/bar/1/hello'));
        $this->assertFalse($r->matchPath('/test-foo/bar/hello/world'));

        // match
        $this->assertTrue($r->matchPath("/test-foo/index/25/foo/bar"));
        $this->assertEquals($r->getParams('controller'), 'test-foo');
        $this->assertEquals($r->getParams('action'), 'index');
        $this->assertEquals($r->getParams(0), '25');
        $this->assertEquals($r->getParams(1), 'foo');
        $this->assertEquals($r->getParams(2), 'bar');
    }

    /**
     * @group generate
     */
    public function testGenerate()
    {
        $r = new Route('/');
        $this->assertFalse($r->generate(array('controller' => 'foo')));
        $this->assertEquals('/', $r->generate());

        $r = new Route('/foo/{action}/');
        $this->assertFalse($r->generate());
        $this->assertFalse($r->generate(array('action' => '')));
        $this->assertEquals('/foo/bar/', $r->generate(array('action' => 'bar')));

        $r = new Route('/{controller}/{action}/');
        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar'));
        $this->assertEquals('/foo/bar/', $url);
        $this->assertFalse($r->generate(array('controller' => 'foo')));
        $this->assertFalse($r->generate(array('controller' => 'foo', 'action' => '')));
        $this->assertFalse($r->generate(array('controller' => '', 'action' => 'bar')));
        $this->assertFalse($r->generate(array('controller' => '', 'action' => '')));

    }

    /**
     * @group generate
     * @group wildcard
     */
    public function testGenerateWithNonGreedyWildcard()
    {
        $r = new Route('/{controller}/{action}/*');
        $this->assertFalse($r->generate(array('controller' => 'foo', 'action' => 'bar')));
        $this->assertFalse($r->generate(array('foo' => 'bar')));
        $this->assertFalse($r->generate(array('controller' => 'test', 'foo' => 'bar')));

        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar', 1));
        $this->assertEquals('/foo/bar/1/', $url);

        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar', 'one', 'two'));
        $this->assertEquals('/foo/bar/one/two/', $url);

        $r = new Route('/{controller}/{action}/{1}-{0}/*');
        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar', 'one', 'two', 'three'));
        $this->assertEquals('/foo/bar/two-one/three/', $url);

    }

    /**
     * @group generate
     * @group wildcard
     */
    public function testGenerateWithGreedyWildcard()
    {
        $r = new Route('/{controller}/{action}/**');
        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar', 'one/two/three'));
        $this->assertEquals('/foo/bar/one/two/three/', $url);

        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar', 'one', 'two', 'three'));
        $this->assertEquals('/foo/bar/one/two/three/', $url);

        $url = $r->generate(array('controller' => 'foo', 'action' => 'bar'));
        $this->assertEquals(false, $url);
    }

}
