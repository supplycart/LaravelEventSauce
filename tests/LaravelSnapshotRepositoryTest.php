<?php

declare(strict_types=1);

namespace Tests;

use EventSauce\EventSourcing\Snapshotting\Snapshot;
use EventSauce\LaravelEventSauce\Snapshotting\LaravelSnapshotRepository;
use EventSauce\LaravelEventSauce\Snapshotting\NativeSnapshotStateSerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\RegistrationAggregateRootId;
use UnexpectedValueException;

final class LaravelSnapshotRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_retrieves_and_replaces_snapshots(): void
    {
        $repository = $this->app->make(LaravelSnapshotRepository::class);
        $id = RegistrationAggregateRootId::create();

        $this->assertNull($repository->retrieve($id));

        $repository->persist(new Snapshot($id, 2, ['registered' => ['first@example.com']]));
        $repository->persist(new Snapshot($id, 3, ['registered' => ['first@example.com', 'second@example.com']]));

        $snapshot = $repository->retrieve($id);

        $this->assertNotNull($snapshot);
        $this->assertSame($id, $snapshot->aggregateRootId());
        $this->assertSame(3, $snapshot->aggregateRootVersion());
        $this->assertSame(
            ['registered' => ['first@example.com', 'second@example.com']],
            $snapshot->state(),
        );
        $this->assertDatabaseCount('domain_snapshots', 1);
    }

    public function test_native_serializer_rejects_invalid_payloads(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new NativeSnapshotStateSerializer())->unserialize('not valid base64!');
    }
}
