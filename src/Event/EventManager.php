<?php
namespace Flow\Event;


class EventManager {

    private $events = [];

    /**
     * Subscribe to an event by name
     */
    public function subscribe($eventName, callable $callback)
    {
        if (!isset($this->events[$eventName])) {
            $this->events[$eventName] = [];
        }

        $this->events[$eventName][] = $callback;
    }

    /**
     * Fire an event
     *
     * @param $eventName
     * @param $eventData
     */
    public function fire($eventName, $eventSource, $eventData)
    {
        if (!isset($this->events[$eventName])) {
           return;
        }
    }
} 