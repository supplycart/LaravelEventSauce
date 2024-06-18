<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class UserWasRegistered implements SerializablePayload
{
    public function __construct(private string $name, private string $email)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            $payload['name'],
            $payload['email']
        );
    }
}
