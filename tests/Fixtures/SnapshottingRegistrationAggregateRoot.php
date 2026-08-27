<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DomainException;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Snapshotting\AggregateRootWithSnapshotting;
use EventSauce\EventSourcing\Snapshotting\SnapshottingBehaviour;

final class SnapshottingRegistrationAggregateRoot implements AggregateRootWithSnapshotting
{
    use AggregateRootBehaviour;
    use SnapshottingBehaviour;

    private array $registered = [];

    public function registerUser(RegisterUser $command): void
    {
        if ($this->hasRegistered($command->email())) {
            throw new DomainException("A user with email address \"{$command->email()}\" was already registered.");
        }

        $this->recordThat(new UserWasRegistered($command->name(), $command->email()));
    }

    public function hasRegistered(string $email): bool
    {
        return in_array($email, $this->registered, true);
    }

    protected function applyUserWasRegistered(UserWasRegistered $event): void
    {
        $this->registered[] = $event->email();
    }

    protected function createSnapshotState(): mixed
    {
        return $this->registered;
    }

    protected static function reconstituteFromSnapshotState(
        AggregateRootId $id,
        mixed $state,
    ): AggregateRootWithSnapshotting {
        $aggregate = new static($id);
        $aggregate->registered = $state;

        return $aggregate;
    }
}
