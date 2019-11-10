<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 10/18/14
 * Time: 12:12 PM
 */

namespace Flow\Test\Container;

use \Flow\Container\AppContainer;

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AppContainer
     */
    public $c;

    public function setUp()
    {
        $this->c = new AppContainer();
    }

    public function testRegister()
    {
        $callable = function () {
            return 'a';
        };
        $c = new AppContainer();
        $c->register('a', $callable);
        $this->assertEquals($callable, $c->raw('a'));
    }

    public function testRegisterNonCallable()
    {
        $c = new AppContainer();
        $c->register('a', 'foo');
        $this->assertEquals('foo', $c->raw('a'));
    }

    public function testResolve()
    {
        $c = new AppContainer();
        $c->register('a', function () {
            return 'a';
        });
        $this->assertEquals('a', $c->resolve('a'));
    }

    public function testResolveNonRegistered()
    {
        $c = new AppContainer();
        $this->assertEquals(null, $c->resolve('a'));
    }

    public function testResolveNonCallable()
    {
        $c = new AppContainer();
        $c->register('a', 'foo');
        $this->assertEquals('foo', $c->resolve('a'));
    }

    public function testFactory()
    {
        $factory = function () {
            return uniqid();
        };

        $c = new AppContainer();
        $c->factory('test_factory', $factory);
        $instance1 = $c->resolve('test_factory');
        $instance2 = $c->resolve('test_factory');

        $this->assertNotEquals($instance1, $instance2);

        // Double Check
        $c = new AppContainer();
        $c->register('test_factory', $factory);
        $instance1 = $c->resolve('test_factory');
        $instance2 = $c->resolve('test_factory');

        $this->assertEquals($instance1, $instance2);
    }

    public function testFactoryNonCallable()
    {
        $this->expectException('InvalidArgumentException');

        $c = new AppContainer();
        $c->factory('test_factory', 'Not a callable');
    }

    public function testProtect()
    {
        $protected = function () {
            return 'protected';
        };

        $c = new AppContainer();
        $c->protect('a', $protected);
        $this->assertEquals('protected', $c->resolve('a'));
    }

    public function testProtectOverwrite()
    {
        $c = new AppContainer();
        $c->protect('a', 'I am protected');

        $this->expectException('Exception');
        $c->register('a', 'Some other value');
    }

    public function testIsProtected()
    {
        $c = new AppContainer();
        $c->protect('a', 'I am protected');
        $c->register('b', 'foo');

        $this->assertTrue($c->isProtected('a'));
        $this->assertFalse($c->isProtected('b'));
    }

    public function testIsFactory()
    {
        $c = new AppContainer();
        $c->factory('a', function () {
            return 'I am a factory' . date('H:i:s');
        });
        $c->register('b', 'foo');

        $this->assertTrue($c->isFactory('a'));
        $this->assertFalse($c->isFactory('b'));
    }

    public function testHasInstance()
    {
        $this->markTestIncomplete();
    }

    public function testHas()
    {
        $c = new AppContainer();
        $c->register('a', 'foo');

        $this->assertTrue($c->has('a'));
        $this->assertFalse($c->has('b'));
    }

    public function testClear()
    {
        $c = new AppContainer();
        $c->register('a', 'foo');
        $c->clear();

        $this->assertFalse($c->has('a'));
    }

    public function testRaw()
    {
        $this->markTestIncomplete();
    }

    public function testOffsetGet()
    {
        $c = new AppContainer();
        $c->register('a', function () {
            return 'foo';
        });
        $this->assertEquals('foo', $c['a']);
    }

    public function testOffsetSet()
    {
        $c = new AppContainer();
        $c['a'] = function () {
            return 'foo';
        };
        $this->assertEquals('foo', $c['a']);

    }

    public function testOffsetExist()
    {
        $c = new AppContainer();
        $c['a'] = function () {
            return 'foo';
        };
        $this->assertTrue(isset($c['a']));
        $this->assertFalse(isset($c['b']));
    }

    public function testOffsetUnset()
    {
        $c = new AppContainer();
        $c['a'] = function () {
            return 'foo';
        };
        $c['a'] = 'foo';
        $this->assertEquals('foo', $c['a']);
    }
}