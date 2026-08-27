<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce\Snapshotting;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Snapshotting\AggregateRootRepositoryWithSnapshotting;
use EventSauce\EventSourcing\Snapshotting\AggregateRootWithSnapshotting;
use EventSauce\EventSourcing\Snapshotting\ConstructingAggregateRootRepositoryWithSnapshotting;
use EventSauce\LaravelEventSauce\AggregateRootRepository;
use EventSauce\LaravelEventSauce\LaravelMessageRepository;
use LogicException;

abstract class SnapshottingAggregateRootRepository extends AggregateRootRepository implements AggregateRootRepositoryWithSnapshotting
{
    public function __construct(
        LaravelMessageRepository $messageRepository,
        private LaravelSnapshotRepository $snapshotRepository,
    ) {
        parent::__construct($messageRepository);

        if (! is_a($this->aggregateRoot, AggregateRootWithSnapshotting::class, true)) {
            throw new LogicException('Snapshotting repositories require an aggregate root that implements AggregateRootWithSnapshotting.');
        }
    }

    public function retrieveFromSnapshot(AggregateRootId $aggregateRootId): object
    {
        return $this->snapshottingRepository()->retrieveFromSnapshot($aggregateRootId);
    }

    public function storeSnapshot(AggregateRootWithSnapshotting $aggregateRoot): void
    {
        $this->snapshottingRepository()->storeSnapshot($aggregateRoot);
    }

    private function snapshottingRepository(): AggregateRootRepositoryWithSnapshotting
    {
        return new ConstructingAggregateRootRepositoryWithSnapshotting(
            $this->aggregateRoot,
            $this->messageRepository,
            $this->snapshotRepository,
            $this->repository(),
        );
    }
}
