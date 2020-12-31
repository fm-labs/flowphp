<?php

namespace Flow\Test\Http\Message;

use Flow\Http\Message\Message;
use Flow\Http\Message\Stream\StringStream;

class MessageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testGetHeaderLine(): void
    {
        $msg = (new Message())
            ->withHeader("foo", "some/header;value");
        $this->assertEquals("some/header;value", $msg->getHeaderLine("foo"));
    }

    /**
     * @return void
     */
    public function testGetHeaders(): void
    {
        $msg = (new Message())
            ->withHeader("foo", "some/header;value")
            ->withHeader("x-test", "a")
            ->withHeader("X-test", "b"); // test case-sensitivity

        $this->assertEquals([
            "foo" => ["some/header;value"],
            "x-test" => ["a"],
            "X-test" => ["b"]
        ], $msg->getHeaders());
    }

    /**
     * @return void
     */
    public function testHasHeader(): void
    {
        $msg = new Message();
        $this->assertFalse($msg->hasHeader("test"));

        $msg = $msg->withHeader("test", "foo");
        $this->assertTrue($msg->hasHeader("test"));
    }

    /**
     * @return void
     */
    public function testWithHeader(): void
    {
        $msg = (new Message())
            ->withHeader("foo", "a")
            ->withHeader("Foo", "b")
            ->withHeader("bar", ["x", "y"]);

        $this->assertEquals(["a"], $msg->getHeader("foo"));
        $this->assertEquals(["b"], $msg->getHeader("Foo")); // test case-sensitivity
        $this->assertEquals(["x", "y"], $msg->getHeader("bar"));
    }

    /**
     * @return void
     */
    public function testWithoutHeader(): void
    {
        $msg = (new Message())
            ->withHeader("test", "foo");
        $this->assertTrue($msg->hasHeader("test"));

        $msg = $msg->withoutHeader("test");
        $this->assertFalse($msg->hasHeader("test"));
    }

    /**
     * @return void
     */
    public function testWithAddedHeader(): void
    {
        $msg = (new Message())
            ->withHeader("foo", "a");
        $this->assertEquals(["foo" => ["a"]], $msg->getHeaders());

        $msg = $msg
            ->withAddedHeader("foo", "b")
            ->withAddedHeader("foo", ["c", "d"]);
        $this->assertEquals(["foo" => ["a", "b", "c", "d"]], $msg->getHeaders());
    }

    /**
     * @return void
     */
    public function testWithProtocolVersion(): void
    {
        $msg = new Message();
        // assert default value
        $this->assertEquals("1.1", $msg->getProtocolVersion());

        $msg1 = $msg->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $msg1->getProtocolVersion());
    }

    /**
     * @return void
     */
    public function testWithBody(): void
    {
        $msg = (new Message())
            ->withBody(new StringStream("test"));

        $this->assertInstanceOf('\Flow\Http\Message\Stream\StringStream', $msg->getBody());
        $this->assertEquals("test", $msg->getBody()->getContents());
    }
}
