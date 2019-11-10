<?php

namespace Flow\Test\Http;

use Flow\Http\Environment;

class EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    protected function getTestEnvironment($env = [])
    {
        $env = array_merge(array(
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost:8080',
            'HTTP_USER_AGENT' => 'Flow Agent',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/index.php/foo/bar?query=test',
            'PATH_INFO' => '/foo/bar',
            'SCRIPT_NAME' => '/index.php',
            'QUERY_STRING' => 'query=test',
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
        ), $env);

        return new Environment($env);
    }

    public function testTestEnvironment()
    {
        $env = $this->getTestEnvironment();

        $this->assertTrue($env instanceof Environment);
        $this->assertEquals($env['SERVER_PROTOCOL'], 'HTTP/1.1');
        $this->assertEquals($env['SERVER_NAME'], 'localhost');
        $this->assertEquals($env['SERVER_PORT'], 80);
        $this->assertEquals($env['REMOTE_ADDR'], '127.0.0.1');
        $this->assertEquals($env['HTTP_HOST'], 'localhost:8080');
        $this->assertEquals($env['HTTP_USER_AGENT'], 'Flow Agent');
        $this->assertEquals($env['REQUEST_METHOD'], 'GET');
        $this->assertEquals($env['SCRIPT_NAME'], '/index.php');
        $this->assertEquals($env['REQUEST_URI'], '/index.php/foo/bar?query=test');
        $this->assertEquals($env['PATH_INFO'], '/foo/bar');
        $this->assertEquals($env['QUERY_STRING'], 'query=test');
    }

    public function testCreateServerRequest()
    {
        $env = $this->getTestEnvironment();
        $req = $env->createServerRequest("GET", "/");

        $this->assertEquals("1.1", $req->getProtocolVersion());
        $this->assertEquals("GET", $req->getMethod());
        $this->assertEquals("localhost:8080", $req->getHeader("Host")[0]);
        $this->assertEquals("Flow Agent", $req->getHeaderLine("User-Agent"));
        $this->assertEquals("/foo/bar?query=test", $req->getRequestTarget());
        $this->assertEquals("localhost", $req->getUri()->getHost());
        $this->assertEquals("8080", $req->getUri()->getPort());
        $this->assertEquals("/foo/bar", $req->getUri()->getPath());
        $this->assertEquals("query=test", $req->getUri()->getQuery());
        $this->assertEquals(["query" => "test"], $req->getQueryParams());

        // incomplete
        $this->assertEquals([], $req->getCookieParams());
        $this->assertEquals([], $req->getAttributes());
        $this->assertEquals([], $req->getUploadedFiles());

        $this->markTestIncomplete();
    }
}