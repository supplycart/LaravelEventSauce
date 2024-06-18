<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final class RegisterUser
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
}
