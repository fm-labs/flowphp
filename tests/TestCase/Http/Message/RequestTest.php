<?php

namespace Flow\Test\Http\Message;

use Flow\Http\Message\Request;
use Flow\Http\Message\Uri;
use FmLabs\Uri\UriFactory;

class RequestTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @return void
     */
    public function testWithRequestTarget(): void
    {
        $req = (new Request())
            ->withRequestTarget("test-target");
        $this->assertEquals("test-target", $req->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testWithMethod(): void
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

    /**
     * @return void
     */
    public function testWithUri(): void
    {
        $uri = "http://user:pass@localhost/my/app?myquery=1#frag";
        $req = (new Request())
            ->withUri(UriFactory::fromString($uri));

        $this->assertEquals((string)$uri, (string)$req->getUri());
    }
}
