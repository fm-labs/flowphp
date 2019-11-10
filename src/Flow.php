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
    static protected $version = "0.1";

    /**
     * Boot method
     * - Loads global functions
     */
    static public function boot()
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
     * @param App\App $app
     */
    static public function serve(\Flow\App\App $app)
    {
        $env = Environment::fromGlobals();
        //$server = static::$serverFactory($env);
        $server = new \Flow\Http\Server\Server($env);
        $server->run($app);
        //static::get('server', 'http_server')->run($app);
    }

    /**
     * Magic static call handler
     *
     * @param $method
     * @param $args
     * @throws \Exception
     */
    static public function __callStatic($method, $args)
    {
        // @todo Implement magic static call
        throw new \Exception("Not implemented");
    }
}