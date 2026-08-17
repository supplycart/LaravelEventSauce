<?php

declare(strict_types=1);

namespace Tests;

use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\LaravelEventSauce\LaravelMessageRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\RegistrationAggregateRootId;
use Tests\Fixtures\UserWasRegistered;

class LaravelMessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private LaravelMessageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(LaravelMessageRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->repository);
    }

    public function test_it_can_persist_messages()
    {
        $message = $this->getUserWasRegisteredMessage();

        $this->repository->persist($message);

        $this->assertDatabaseHas('domain_messages', [
            'id' => 1,
            'event_type' => 'tests.fixtures.user_was_registered',
        ]);
    }

    public function test_it_can_retrieve_messages()
    {
        $id = RegistrationAggregateRootId::create();
        $message = $this->getUserWasRegisteredMessage($id);

        $this->repository->persist($message);

        $messages = $this->repository->retrieveAll($id);

        foreach ($messages as $message) {
            $this->assertEquals($id, $message->aggregateRootId());
            $this->assertInstanceOf(UserWasRegistered::class, $message->event());
        }

        $this->assertSame(1, $messages->getReturn());
    }

    public function test_it_can_paginate_messages()
    {
        $this->repository->persist(
            $this->getUserWasRegisteredMessage(),
            $this->getUserWasRegisteredMessage(),
        );

        $messages = $this->repository->paginate(OffsetCursor::fromStart(limit: 1));

        $this->assertCount(1, iterator_to_array($messages));
        $this->assertSame('1|1', $messages->getReturn()->toString());
    }

    public function test_it_can_retrieve_messages_after_an_aggregate_version()
    {
        $id = RegistrationAggregateRootId::create();
        $decorator = new DefaultHeadersDecorator();

        $messages = collect([1, 2, 3])->map(fn (int $version) => $decorator->decorate(
            new Message(
                new UserWasRegistered("User {$version}", "user{$version}@example.com"),
                [
                    Header::AGGREGATE_ROOT_ID => $id,
                    Header::AGGREGATE_ROOT_VERSION => $version,
                ],
            ),
        ));

        $this->repository->persist(...$messages);

        $retrieved = $this->repository->retrieveAllAfterVersion($id, 1);
        $events = array_map(
            fn (Message $message) => $message->event()->email(),
            iterator_to_array($retrieved),
        );

        $this->assertSame(['user2@example.com', 'user3@example.com'], $events);
        $this->assertSame(3, $retrieved->getReturn());
    }
}
