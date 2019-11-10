<?php

namespace Flow\Container;


use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var array Map of object factories and item options
     */
    protected $registry = [];

    /**
     * @var array Map of registered objects
     */
    protected $objects = [];

    /**
     * Register a new object or factory.
     * 
     * Available options:
     * - `protect`: boolean If TRUE, a re-register attempt with the same id will throw a ContainerException.
     * - `single`: boolean If TRUE, an instance created from a factory will always be the same instance when resolved.
     *
     * @param $id
     * @param $obj
     * @param array $opts 
     * @return $this
     */
    public function register($id, $obj, $opts = [])
    {
        $opts += ['protected' => null, 'single' => null];

        if (isset($this->registry[$id])) {
            $_opts = $this->registry[$id]['opts'] ?? [];
            $_protected = $_opts['protected'] ?? false;
            if ($_protected) {
                throw new ContainerException("Container REGISTER operation not allowed on already registered and protected item");
            }
        }

        // We register callables as factories and
        // all other values as named objects
        if ($obj instanceof \Closure) {
            $factory = $obj;
            $obj = null;
        } else {
            $factory = null;
            $opts['single'] = true;
            $this->objects[$id] = $obj;
        }
        $this->registry[$id] = ['factory' => $factory, 'opts' => $opts];

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

        if (isset($this->registry[$id])) {
            unset($this->registry[$id]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        $obj = $this->objects[$id] ?? null;
        if ($obj) {
            return $obj;
        }

        // try to construct object from a factory, if registered
        $reg = $this->registry[$id] ?? null;
        if ($reg === null) {
            throw new NotFoundException("Container GET: Unknown object identifier `" . $id . "`");
        }

        $factory = $reg['factory'];
        $opts = $reg['opts'];

        if (is_callable($factory)) {
            $obj = $factory();
            if ($obj) {
                if ($opts['single'] === true) {
                    $this->objects[$id] = $obj;
                }
                return $obj;
            }
        }

        throw new NotFoundException("Container ITEM not Found: `" . $id . "`");
    }

    /**
     * {@inheritDoc}
     */
    public function has($id)
    {
        return isset($this->registry[$id]);
    }

}