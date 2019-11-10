<?php

namespace Flow\Container;

/**
 * Interface ServiceLocatorInterface
 * @package Flow\Container
 * @deprecated
 */
interface ServiceLocatorInterface
{
    public function register($name, $service);

    public function resolve($name);

    public function has($name);

    public function remove($name);

    public function clear();
}
