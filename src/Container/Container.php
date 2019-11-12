<?php

namespace Flow\Container;


use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var array Map of registered objects
     */
    protected $objects = [];

    public function factory($id, callable $factory = null)
    {
        return $this->register('_factory_' . $id, $factory);
    }

    public function spawn($id, $args = [])
    {
        $factory = $this->get('_factory_' . $id);
        return call_user_func_array($factory, $args);
    }

    /**
     * Register a new object or factory.
     *
     * @param $id
     * @param $obj
     * @return $this
     */
    public function register($id, $obj)
    {
        $this->objects[$id] = $obj;

        return $this;
    }

    /**
     * Remove an item from the container.
     *
     * @param $id
     */
    public function unregister($id)
    {
        if (isset($this->objects[$id])) {
            $this->objects[$id] = null;
            unset($this->objects[$id]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        $obj = $this->objects[$id] ?? null;
        if (!$obj) {
            throw new NotFoundException();
        }

        return $obj;
    }

    /**
     * {@inheritDoc}
     */
    public function has($id)
    {
        return isset($this->objects[$id]);
    }

}