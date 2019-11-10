<?php

namespace Flow\Test\App;

use Flow\App\App;
use Flow\Http\Environment;
use Flow\Http\Message\Request;
use Flow\Http\Message\Response;

class AppTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Environment
     */
    protected $env;

    public function setUp()
    {
        $this->env = Environment::mock();
    }

    public function testZeroConfigApp()
    {
        $this->markTestIncomplete();

        $app = new App();
    }

    public function testInvokeController()
    {
        $this->markTestIncomplete();

        $app = new App();

        // static invocation by name
        //$app->invokeController('\\FlowTestApp\\Controller\\HelloWorldController', 'index', ['foo' => 'bar']);

        // static invocation with callable
        //$app->invokeController(['\\FlowTestApp\\Controller\\HelloWorldController', 'index'], ['foo' => 'bar']);

    }

}
