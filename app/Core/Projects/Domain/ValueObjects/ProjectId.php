<?php

namespace Core\Projects\Domain\ValueObjects;

use InvalidArgumentException;

class ProjectId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("Project ID must be positive");
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(ProjectId $other): bool
    {
        return $this->value === $other->getValue();
    }

    public static function generate(): self
    {
        return new self(rand(1, 1000000));
    }
}