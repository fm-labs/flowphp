<?php
namespace Flow\Object;

trait WithPropertyTrait
{
    /**
     * @param $property
     * @param $val
     * @return static A cloned instance with changed property value
     */
    protected function with($property, $val)
    {
        $clone = clone $this;
        $clone->{$property} = $val;

        return $clone;
    }

    /**
     * @param $property
     * @param null $defaultVal
     * @return static
     */
    protected function without($property, $defaultVal = null)
    {
        $clone = clone $this;
        $clone->{$property} = $defaultVal;

        return $clone;
    }

    /**
     * @param $property
     * @param $key
     * @param $val
     * @return static
     */
    protected function withKey($property, $key, $val)
    {
        $clone = clone $this;
        $clone->{$property}[$key] = $val;

        return $clone;
    }

    /**
     * @param $property
     * @param $key
     * @return static
     */
    protected function withoutKey($property, $key)
    {
        $clone = clone $this;
        if (isset($clone->{$property}[$key])) {
            unset($clone->{$property}[$key]);
        }

        return $clone;
    }
}
