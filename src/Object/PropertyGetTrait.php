<?php

namespace Flow\Object;

trait PropertyGetTrait
{
    public function get(string $key)
    {
        // custom getters
        $method = 'get' . ucfirst($key);
        if (method_exists($this, $method)) {
            return call_user_func([$this, $method]);
        }

        return ($this->{$key}) ?? null;
    }
}
