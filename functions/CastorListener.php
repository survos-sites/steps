<?php
// file: src/EventListener/CastorListener.php

use Castor\Attribute\AsListener;
use Castor\Event\AfterExecuteTaskEvent;
use Castor\Event\ContextCreatedEvent;

if (!function_exists('my_event_listener')) {
    #[AsListener(ContextCreatedEvent::class)]
    function my_event_listener(AfterExecuteTaskEvent|ContextCreatedEvent $event): void
    {
        // Custom logic to handle the events
        echo "Event triggered: " . $event::class . "\n";
    }

    #[AsListener(ContextCreatedEvent::class, priority: 10)]
    function high_priority_listener(ContextCreatedEvent $event): void
    {
        // This listener will run before the one above due to higher priority
        echo "High priority listener\n";
    }

    #[AsListener(AfterExecuteTaskEvent::class, priority: 10)]
    function AfterExecuteTaskEvent(AfterExecuteTaskEvent $event): void
    {
        // This listener will run before the one above due to higher priority
        echo "High priority listener\n";
    }
}

