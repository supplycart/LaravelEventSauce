<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class HandleConsumer implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /** @var Message[] */
    private $messages = [];

    public function __construct(private string $consumer, Message ...$messages)
    {
        $this->messages = $messages;
    }

    public function handle(Container $container): void
    {
        $consumer = $this->resolveConsumer($container);

        foreach ($this->messages as $message) {
            $consumer->handle($message);
        }
    }

    private function resolveConsumer(Container $container): Consumer
    {
        return $container->make($this->consumer);
    }
}
