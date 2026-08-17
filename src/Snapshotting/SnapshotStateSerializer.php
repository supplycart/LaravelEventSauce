<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce\Snapshotting;

interface SnapshotStateSerializer
{
    public function serialize(mixed $state): string;

    public function unserialize(string $payload): mixed;
}
