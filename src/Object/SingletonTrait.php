<?php


namespace Flow\Object;


trait SingletonTrait
{
    /**
     * @var array Holds the singleton instance
     */
    static private $singleton = [];

    //static private $singletonProtectGet = false; // *experimental* The object has to be initialized with 'new' to self-register the instance
    //static private $singletonProtectSet = false; // *not implemented* The singleton can only be set by itself (usually at the at of the object constructor), thus not replaced from the outside

    /**
     * Get the singleton instance.
     *
     * @return SingletonTrait|static|null
     */
    static public function getInstance()
    {
        $obj = (static::$singleton[0]) ?? null;
        if ($obj) {
            return $obj;
        }

        /* experimental */
        if (!isset(static::$singletonProtectGet)) {
            $obj = new static();
            return static::setInstance($obj);
        }

        throw new \RuntimeException("The protected Singleton class has not been initialized yet. External initialization not allowed");
    }

    /**
     * Set the singleton instance.
     * @param SingletonTrait $obj
     * @return SingletonTrait
     */
    static public function setInstance(self $obj)
    {
        return static::$singleton[0] = $obj;
    }
}