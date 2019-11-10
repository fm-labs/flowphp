<?php

namespace Flow\Event;


class Event {

    protected $name;

    protected $subject;

    protected $data;

    public function __construct($name, $subject = null, $data = [])
    {
        $this->name = $name;
        $this->subject = $subject;
        $this->data = $data;
    }

    public function name()
    {
        return $this->name;
    }

    public function subject()
    {
        return $this->subject;
    }

    public function data($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->data;
    }
}