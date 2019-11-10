<?php

namespace Flow\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class Container
 *
 * @package Flow\Container
 *
 * @todo Magic getter to resolve objects
 * @todo Drop array access (?)
 * @todo Change `register` method to automatically register factories not singletons
 */
class AppContainer implements ContainerInterface, \ArrayAccess
{
    protected $callables = array();

    protected $instances = array();

    protected $factories = array();

    protected $protected = array();

    /**
     * Registers a callable for a name.
     *
     * Defaults to a singleton behavior.
     * The same object will be resolved every time.
     *
     * @param $name
     * @param $callable
     * @throws \Exception
     * @return $this
     */
    public function register($name, $callable)
    {
        if ($this->has($name) && $this->isProtected($name)) {
            throw new \Exception("Unable to register $name: Protected");
        }

        //if (!is_callable($callable)) {
        //    $callable = function () use ($callable) {
        //        return $callable;
        //    };
        //}

        $this->callables[$name] = $callable;
        return $this;
    }

    /**
     * Factory
     *
     * Every time the name resolves,
     * a new instance of the callable will be returned
     *
     * @param $name
     * @param $callable
     * @return $this
     * @throws \Exception
     */
    public function factory($name, $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("A factory MUST be a valid callable");
        }
        $this->factories[$name] = true;
        return $this->register($name, $callable);
    }

    /**
     * Protected instances can't be changed / overwritten
     *
     * @param $name
     * @param $callable
     * @return $this
     * @throws \Exception
     */
    public function protect($name, $callable)
    {
        $this->protected[$name] = true;
        return $this->register($name, $callable);
    }

    /**
     * Singleton
     *
     * The same instance will be returned on every call
     *
     * @param $name
     * @param $callable
     * @return $this
     * @throws \Exception
     */
    public function singleton($name, $callable)
    {
        return $this->register($name, $callable);
    }

    /**
     * Resolve instance by name
     *
     * @param $name
     * @return mixed
     * @throws \RuntimeException
     */
    public function resolve($name)
    {
        if (!isset($this->callables[$name])) {
            //throw new \RuntimeException("Unable to resolve index: $name");
            return null;
        }

        if (!is_callable($this->callables[$name])) {
            return $this->callables[$name];
        }

        if (!isset($this->instances[$name])) {
            $instance = call_user_func($this->callables[$name], $this);

            // do not keep factory instances
            if (!$this->isFactory($name)) {
                $this->instances[$name] = $instance;
            }
        } else {
            $instance = $this->instances[$name];
        }

        return $instance;
    }

    /**
     * Returns the raw callable
     *
     * @param $name
     * @return null
     * @deprecated This method is pretty useless.
     */
    public function raw($name)
    {
        if (isset($this->callables[$name])) {
            return $this->callables[$name];
        }
        return null;
    }

    /**
     * Unregister object from container
     *
     * Shutdown instance and remove object from stack
     *
     * @param $name
     * @return $this
     */
    public function unregister($name)
    {
        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
        }
        if (isset($this->factories[$name])) {
            unset($this->factories[$name]);
        }
        if (isset($this->protected[$name])) {
            unset($this->protected[$name]);
        }
        if (isset($this->callables[$name])) {
            unset($this->callables[$name]);
        }
        return $this;
    }

    /**
     * Check if registered
     *
     * @param $name
     * @return bool
     * @deprecated Use has() instead
     */
    public function isRegistered($name)
    {
        return isset($this->callables[$name]);
    }

    /**
     * @param $name
     * @return bool
     * @todo Remove useless methods
     */
    public function isProtected($name)
    {
        return isset($this->protected[$name]);
    }

    /**
     * @param $name
     * @return bool
     * @todo Remove useless methods
     */
    public function isFactory($name)
    {
        return isset($this->factories[$name]);
    }

    /**
     * @param $name
     * @return bool
     * @todo Remove useless methods
     */
    public function isInstance($name)
    {
        return isset($this->instances[$name]);
    }
    /**
     * Clear stack
     *
     * @return $this
     * @todo Remove useless methods
     */
    public function clear()
    {
        $this->instances = array();
        $this->factories = array();
        $this->protected = array();
        $this->callables = array();

        return $this;
    }


    /**
     * ArrayAccess Exists
     *
     * @param mixed $offset
     * @return bool
     * @todo Remove useless methods
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess Get
     *
     * @param mixed $offset
     * @return mixed
     * @todo Remove useless methods
     */
    public function offsetGet($offset)
    {
        return $this->resolve($offset);
    }

    /**
     * ArrayAccess Set
     *
     * @param mixed $offset
     * @param mixed $val
     * @return $this|void
     * @todo Remove useless methods
     */
    public function offsetSet($offset, $val)
    {
        return $this->register($offset, $val);
    }

    /**
     * ArrayAccess Unset
     *
     * @param mixed $offset
     * @return $this|void
     * @todo Remove useless methods
     */
    public function offsetUnset($offset)
    {
        return $this->unregister($offset);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     */
    public function get($id)
    {
        return $this->resolve($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->isRegistered($id);
    }
}
