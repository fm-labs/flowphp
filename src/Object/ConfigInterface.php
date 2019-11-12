<?php


namespace Flow\Object;


interface ConfigInterface
{
    /**
     * @param null|string|array $key
     * @return array|null
     */
    public function config($key = null);
}