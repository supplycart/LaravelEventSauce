<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce\Snapshotting;

use UnexpectedValueException;

final class NativeSnapshotStateSerializer implements SnapshotStateSerializer
{
    public function serialize(mixed $state): string
    {
        return base64_encode(serialize($state));
    }

    public function unserialize(string $payload): mixed
    {
        $serialized = base64_decode($payload, true);

        if ($serialized === false) {
            throw new UnexpectedValueException('The snapshot state is not valid base64.');
        }

        return unserialize($serialized, ['allowed_classes' => true]);
    }
}
