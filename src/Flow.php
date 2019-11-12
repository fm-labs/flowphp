<?php

namespace Flow;

use Flow\Http\Environment;

/**
 * Class Flow
 * @package Flow
 *
 * @method static cache ($key = null, $val = null, $config = null)
 */
class Flow
{
    /**
     * @var string Library version string
     */
    protected static $version = "0.1";

    /**
     * Boot method
     * - Loads global functions
     */
    public static function boot()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        require_once dirname(__FILE__) . "/functions.php";
    }

    /**
     * Returns library slug in format `flowphp/[version]`
     *
     * @return string
     */
    public static function name()
    {
        return "flowphp/" . static::$version;
    }

    /**
     * Helper method to build class names from package names.
     *
     * @param $package
     * @param $name
     * @param string $suffix
     * @return string Namespaced class location
     */
    public static function className($package, $name, $suffix = "")
    {
        $appClass = new \ReflectionClass(get_called_class());
        $appNs = $appClass->getNamespaceName();

        $class = $appNs . "\\" . $package . "\\" . ucfirst($name) . $suffix;
        return $class;
    }

    /**
     * Convenience method to start a http server
     * and run an app.
     *
     * @param \Flow\App\App|callable $app If a callable is used,
     *    the callable MUST return an instance of `RequestHandlerInterface`
     */
    public static function serve(\Flow\App\App $app)
    {
        try {
            $handler = $app();
            $env = Environment::fromGlobals();
            $server = (new \Flow\Http\Server\Server())
                ->setEnvironment($env)
                ->run($handler);
            //static::get('server', 'http_server')->run($app);
        } catch (\Exception $ex) {
            // @todo Error logging
            die("[CRITICAL] Unhandled Server Error: " . $ex->getMessage());
        }
    }

    /**
     * Magic static call handler
     *
     * @param $method
     * @param $args
     * @throws \Exception
     */
    public static function __callStatic($method, $args)
    {
        // @todo Implement magic static call
        throw new \Exception("Not implemented");
    }
}
