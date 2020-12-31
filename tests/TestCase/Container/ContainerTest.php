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

    /**
     * @return void
     */
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->c = new AppContainer();
    }

    /**
     * @return void
     */
    public function testRegister(): void
    {
        $callable = function () {
            return 'a';
        };
        $c = new AppContainer();
        $c->register('a', $callable);
        $this->assertEquals($callable, $c->raw('a'));
    }

    /**
     * @return void
     */
    public function testRegisterNonCallable(): void
    {
        $c = new AppContainer();
        $c->register('a', 'foo');
        $this->assertEquals('foo', $c->raw('a'));
    }

    /**
     * @return void
     */
    public function testResolve(): void
    {
        $c = new AppContainer();
        $c->register('a', function () {
            return 'a';
        });
        $this->assertEquals('a', $c->resolve('a'));
    }

    /**
     * @return void
     */
    public function testResolveNonRegistered(): void
    {
        $c = new AppContainer();
        $this->assertEquals(null, $c->resolve('a'));
    }

    /**
     * @return void
     */
    public function testResolveNonCallable(): void
    {
        $c = new AppContainer();
        $c->register('a', 'foo');
        $this->assertEquals('foo', $c->resolve('a'));
    }

    /**
     * @return void
     */
    public function testFactory(): void
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

    /**
     * @return void
     */
    public function testFactoryNonCallable(): void
    {
        $this->expectException('InvalidArgumentException');

        $c = new AppContainer();
        $c->factory('test_factory', 'Not a callable');
    }

    /**
     * @return void
     */
    public function testProtect(): void
    {
        $protected = function () {
            return 'protected';
        };

        $c = new AppContainer();
        $c->protect('a', $protected);
        $this->assertEquals('protected', $c->resolve('a'));
    }

    /**
     * @return void
     */
    public function testProtectOverwrite(): void
    {
        $c = new AppContainer();
        $c->protect('a', 'I am protected');

        $this->expectException('Exception');
        $c->register('a', 'Some other value');
    }

    /**
     * @return void
     */
    public function testIsProtected(): void
    {
        $c = new AppContainer();
        $c->protect('a', 'I am protected');
        $c->register('b', 'foo');

        $this->assertTrue($c->isProtected('a'));
        $this->assertFalse($c->isProtected('b'));
    }

    /**
     * @return void
     */
    public function testIsFactory(): void
    {
        $c = new AppContainer();
        $c->factory('a', function () {
            return 'I am a factory' . date('H:i:s');
        });
        $c->register('b', 'foo');

        $this->assertTrue($c->isFactory('a'));
        $this->assertFalse($c->isFactory('b'));
    }

    /**
     * @return void
     */
    public function testHasInstance(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @return void
     */
    public function testHas(): void
    {
        $c = new AppContainer();
        $c->register('a', 'foo');

        $this->assertTrue($c->has('a'));
        $this->assertFalse($c->has('b'));
    }

    /**
     * @return void
     */
    public function testClear(): void
    {
        $c = new AppContainer();
        $c->register('a', 'foo');
        $c->clear();

        $this->assertFalse($c->has('a'));
    }

    /**
     * @return void
     */
    public function testRaw(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @return void
     */
    public function testOffsetGet(): void
    {
        $c = new AppContainer();
        $c->register('a', function () {
            return 'foo';
        });
        $this->assertEquals('foo', $c['a']);
    }

    /**
     * @return void
     */
    public function testOffsetSet(): void
    {
        $c = new AppContainer();
        $c['a'] = function () {
            return 'foo';
        };
        $this->assertEquals('foo', $c['a']);

    }

    /**
     * @return void
     */
    public function testOffsetExist(): void
    {
        $c = new AppContainer();
        $c['a'] = function () {
            return 'foo';
        };
        $this->assertTrue(isset($c['a']));
        $this->assertFalse(isset($c['b']));
    }

    /**
     * @return void
     */
    public function testOffsetUnset(): void
    {
        $c = new AppContainer();
        $c['a'] = function () {
            return 'foo';
        };
        $c['a'] = 'foo';
        $this->assertEquals('foo', $c['a']);
    }
}