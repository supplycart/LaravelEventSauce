<?php

declare(strict_types=1);

namespace Tests;

use EventSauce\LaravelEventSauce\Snapshotting\SnapshottingAggregateRootRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Fixtures\RegisterUser;
use Tests\Fixtures\RegistrationAggregateRoot;
use Tests\Fixtures\RegistrationAggregateRootId;
use Tests\Fixtures\SnapshottingRegistrationAggregateRoot;
use Tests\Fixtures\SnapshottingRegistrationAggregateRootRepository;

final class SnapshottingAggregateRootRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_a_snapshot_and_applies_only_newer_events(): void
    {
        $repository = $this->app->make(SnapshottingRegistrationAggregateRootRepository::class);
        $id = RegistrationAggregateRootId::create();

        $aggregate = $repository->retrieve($id);
        $aggregate->registerUser(new RegisterUser('First', 'first@example.com'));
        $repository->persist($aggregate);
        $repository->storeSnapshot($aggregate);

        $aggregate->registerUser(new RegisterUser('Second', 'second@example.com'));
        $repository->persist($aggregate);

        $restored = $repository->retrieveFromSnapshot($id);

        $this->assertInstanceOf(SnapshottingRegistrationAggregateRoot::class, $restored);
        $this->assertTrue($restored->hasRegistered('first@example.com'));
        $this->assertTrue($restored->hasRegistered('second@example.com'));
        $this->assertSame(2, $restored->aggregateRootVersion());
    }

    public function test_it_falls_back_to_the_event_stream_without_a_snapshot(): void
    {
        $repository = $this->app->make(SnapshottingRegistrationAggregateRootRepository::class);
        $id = RegistrationAggregateRootId::create();

        $aggregate = $repository->retrieveFromSnapshot($id);

        $this->assertInstanceOf(SnapshottingRegistrationAggregateRoot::class, $aggregate);
        $this->assertSame(0, $aggregate->aggregateRootVersion());
    }

    public function test_it_rejects_non_snapshotting_aggregate_roots(): void
    {
        $this->expectException(LogicException::class);

        $this->app->make(InvalidSnapshottingRepository::class);
    }
}

final class InvalidSnapshottingRepository extends SnapshottingAggregateRootRepository
{
    protected string $aggregateRoot = RegistrationAggregateRoot::class;
}
