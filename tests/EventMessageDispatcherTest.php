<?php

declare(strict_types=1);

namespace Tests;

use EventSauce\LaravelEventSauce\EventMessageDispatcher;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\UserWasRegistered;

class EventMessageDispatcherTest extends TestCase
{
    public function test_it_can_dispatch_messages()
    {
        $message = $this->getUserWasRegisteredMessage();

        Event::fake();

        $this->dispatcher()->dispatch($message);

        Event::assertDispatched(UserWasRegistered::class);
    }

    private function dispatcher(): EventMessageDispatcher
    {
        return new EventMessageDispatcher();
    }
}
