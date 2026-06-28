<?php

namespace App\Domains\Auth\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $normalized = strtolower(trim($email));

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
