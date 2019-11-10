<?php
namespace Flow\App;


use Flow\Object\ConfigTrait;

class Configuration
{
    use ConfigTrait;

    public function __construct(array $config = [])
    {
        $this->config($config);
    }

    public function get($key)
    {
        return $this->config($key);
    }

    public function set($key, $val = null)
    {
        if (is_array($key)) {
            $this->config($key);
        } else {
            $this->config([$key => $val]);
        }

        return $this;
    }

    public function clear()
    {
        $this->config = null;

        return $this;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $val)
    {
        $this->set($key, $val);
    }

    /*
    public function import($configFile)
    {
        $configFilePath = APP_ROOT . "config" . DIRECTORY_SEPARATOR . $configFile . '.php';
        if (!file_exists($configFilePath)) {
            throw new \Exception("Configuration file $configFile not found");
        }

        $importer = function (Configuration $c, $filePath) {
            include $filePath;
            if (!isset($config) || !is_array($config)) {
                return false;
            }

            $c->append($config);
            unset($config);
            return true;
        };

        return $importer($this, $configFilePath);
    }
    */
}
