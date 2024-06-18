<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce;

use EventSauce\EventSourcing\Consumer as EventSauceConsumer;
use EventSauce\EventSourcing\Message;

abstract class Consumer implements EventSauceConsumer
{
    public function handle(Message $message): void
    {
        $event = $message->event();
        $parts = explode('\\', $event::class);
        $method = 'handle'.end($parts);

        if (method_exists($this, $method)) {
            $this->{$method}($event, $message);
        }
    }
}
