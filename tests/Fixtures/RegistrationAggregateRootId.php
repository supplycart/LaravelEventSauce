<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use EventSauce\EventSourcing\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class RegistrationAggregateRootId implements AggregateRootId
{
    public function __construct(private string $identifier)
    {
    }

    public function toString(): string
    {
        return $this->identifier;
    }

    public function toUuid(): UuidInterface
    {
        return Uuid::fromString($this->identifier);
    }

    public static function create(): self
    {
        return new static(Uuid::uuid4()->toString());
    }

    public static function fromString(string $aggregateRootId): static
    {
        return new static($aggregateRootId);
    }
}
