<?php

namespace Flow\Container;

/**
 * Class StaticContainer
 *
 * @package Flow\Container
 *
 * ! FOR DEVELOPMENT ONLY !
 * ! DO NOT USE IN PRODUCTION !
 */
class StaticContainer
{
    /**
     * @var array Map of registered factories
     */
    static protected $factories = [];

    /**
     * @var array Map of registered factories
     */
    static protected $singletons = [];

    /**
     * @var array Map of registered objects
     */
    static protected $objects = [];

    static public function __test__()
    {
        self::singleton('conf', function() {
            return new \Flow\App\Configuration();
        });

        self::factory('template', function($config = []) {
            $config = array_merge((array)static::$config('template'), $config);
            return \Flow\Template\Template::create($config); // uses StaticCreator
        });

        self::factory('server', function($config = []) {
            $env = \Flow\Http\Environment::fromGlobals();
            return \Flow\Http\Server\Server::create($env); // uses StaticCreator
        });

        self::factory('router', function() {
            return new \Flow\Router\Router();
        });
    }

    static public function __config__($key = null, $value = null)
    {
        return static::singleton('conf')->get($key, $value);
    }

    static public function get($factoryName, $registerAs = null, $args = [])
    {
        if (!isset(static::$factories[$factoryName])) {
            throw new \RuntimeException("Flow factory is not registered: $factoryName");
        }

        if ($registerAs && isset(self::$objects[$registerAs])) {
            return self::$objects[$registerAs];
        }

        $obj = call_user_func_array(static::$factories[$factoryName], $args);
        if ($registerAs) {
            static::$objects[$registerAs] = $obj;
        }

        return $obj;
    }

    static public function factory($id, callable $factory = null)
    {
        // getter
        if ($factory === null) {
            if (!isset(static::$factories[$id])) {
                throw new \RuntimeException("Flow factory not found: $id");
            }

            return static::$factories[$id];
        }

        // setter
        if (!is_callable($factory)) {
            throw new \RuntimeException("Flow singleton factory MUST be a callable");
        }
        static::$factories[$id] = $factory;
    }

    static public function singleton($id, callable $singleton = null)
    {
        // getter
        if ($singleton === null) {
            if (!isset(static::$singletons[$id]) || !isset(static::$singletons[$id][0])) {
                throw new \RuntimeException("Flow singleton not found: $id");
            }

            return static::$singletons[$id][0];
        }

        // setter
        if (isset(static::$singletons[$id]) && isset(static::$singletons[$id][0])) {
            //throw new \RuntimeException("Flow singleton alredy initialized: $id");
            //@TODO Handle duplicate singletons registration issue
            return static::$singletons[$id][0];
        } else {
            if (!is_callable($singleton)) {
                throw new \RuntimeException("Flow singleton factory MUST be a callable");
            }
            $instance = call_user_func($singleton);
            return static::$singletons[$id] = [$instance];
        }
    }
}