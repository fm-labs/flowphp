<?php
namespace Flow\Object;

trait ConfigTrait
{
    /**
     * This property should not be accessed directly, at least call config() once to initialize, before accessing it.
     * It is recommended to use config() method to read config.
     * @var array Current config values
     */
    protected $config;

    /**
     * @var array Default config values
     */
    //protected $defaultConfig = [];

    /**
     * @param null|string|array $key
     * @return array|null
     */
    public function config($key = null)
    {
        // init config values
        if ($this->config === null) {
            $this->config = $this->defaultConfig ?? [];
        }

        if (is_string($key)) {
            return $this->config[$key] ?? null;
        } elseif (is_array($key)) {
            $this->config = array_merge($this->config, $key);
        }

        return $this->config;
    }
}