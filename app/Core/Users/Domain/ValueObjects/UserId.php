<?php

namespace Core\Users\Domain\ValueObjects;

use InvalidArgumentException;

class UserId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("User ID must be positive");
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->getValue();
    }

    public static function generate(): self
    {
        // En realidad será autoincremental de la base de datos
        // Esto es solo para la entidad antes de persistir
        return new self(rand(1, 1000000));
    }
}