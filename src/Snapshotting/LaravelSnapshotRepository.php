<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce\Snapshotting;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Snapshotting\Snapshot;
use EventSauce\EventSourcing\Snapshotting\SnapshotRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;

final class LaravelSnapshotRepository implements SnapshotRepository
{
    private string $connection;

    private string $table;

    public function __construct(
        private DatabaseManager $database,
        private SnapshotStateSerializer $serializer,
        Config $config,
    ) {
        $this->connection = (string) ($config->get('eventsauce.snapshot_connection') ?: $config->get('eventsauce.connection'));
        $this->table = (string) $config->get('eventsauce.snapshot_table', 'domain_snapshots');
    }

    public function persist(Snapshot $snapshot): void
    {
        $id = $snapshot->aggregateRootId();

        $this->connection()->table($this->table)->updateOrInsert(
            [
                'aggregate_root_type' => $id::class,
                'event_stream' => $id->toString(),
            ],
            [
                'aggregate_root_version' => $snapshot->aggregateRootVersion(),
                'state' => $this->serializer->serialize($snapshot->state()),
                'updated_at' => now(),
            ],
        );
    }

    public function retrieve(AggregateRootId $id): ?Snapshot
    {
        $stored = $this->connection()->table($this->table)
            ->where('aggregate_root_type', $id::class)
            ->where('event_stream', $id->toString())
            ->first();

        if ($stored === null) {
            return null;
        }

        return new Snapshot(
            $id,
            (int) $stored->aggregate_root_version,
            $this->serializer->unserialize($stored->state),
        );
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    private function connection(): ConnectionInterface
    {
        return $this->database->connection($this->connection);
    }
}
