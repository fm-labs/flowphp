<?php
namespace Flow\Object;

trait StaticFactoryTrait
{
    /**
     * Create a new instance of class.
     *
     * All arguments passed to this method will be applied as constructor arguments in the same order.
     *
     * @param mixed $arg (optional)
     * @param mixed "arg..." Zero, One or Many arguments, that will be passed to the constructor
     * @return static|object|null A new instance of this class
     */
    static public function create()
    {
        try {
            $re = new \ReflectionClass(self::class);
            $instance = $re->newInstanceArgs(func_get_args());

            return $instance;
        } catch (\ReflectionException $ex) {
            throw new \RuntimeException(sprintf(
                "Factory object initialization failed for class `%s`",
                self::class
            ));
        }
    }
}