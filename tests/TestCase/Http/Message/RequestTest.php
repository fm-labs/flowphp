<?php

namespace Flow\Test\Http\Message;

use Flow\Http\Message\Request;
use Flow\Http\Message\Uri;

class RequestTest extends \PHPUnit\Framework\TestCase
{

    public function testWithRequestTarget()
    {
        $req = (new Request())
            ->withRequestTarget("test-target");
        $this->assertEquals("test-target", $req->getRequestTarget());
    }

    public function testWithMethod()
    {
        $req = (new Request())
            ->withMethod("POST");
        $this->assertEquals("POST", $req->getMethod());

        // test case sensitivity
        $req = (new Request())
            ->withMethod("post");
        $this->assertEquals("post", $req->getMethod());
        $this->assertNotEquals("POST", $req->getMethod());

        // test invalid http methods
        $this->expectException('\InvalidArgumentException');
        $req = (new Request())
            ->withMethod("asdf");

    }

    public function testWithUri()
    {
        $uri = "http://user:pass@localhost/my/app?myquery=1#frag";
        $req = (new Request())
            ->withUri(new Uri($uri));

        $this->assertEquals((string)$uri, (string)$req->getUri());
    }
}
