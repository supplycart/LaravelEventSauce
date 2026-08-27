<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use EventSauce\LaravelEventSauce\Snapshotting\SnapshottingAggregateRootRepository;

final class SnapshottingRegistrationAggregateRootRepository extends SnapshottingAggregateRootRepository
{
    protected string $aggregateRoot = SnapshottingRegistrationAggregateRoot::class;
}
