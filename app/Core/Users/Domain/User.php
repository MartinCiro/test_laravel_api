<?php

namespace Core\Users\Domain;

use Core\Users\Domain\ValueObjects\UserId;
use Core\Users\Domain\ValueObjects\Email;
use InvalidArgumentException;

class User
{
    public function __construct(
        private UserId $id,
        private string $name,
        private Email $email,
        private string $password,
        private \DateTime $createdAt,
        private \DateTime $updatedAt,
        private ?string $rememberToken = null,
        private ?\DateTime $emailVerifiedAt = null
    ) {
        $this->validateName($name);
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException("User name cannot be empty");
        }

        if (strlen($name) < 2) {
            throw new InvalidArgumentException("User name must be at least 2 characters long");
        }

        if (strlen($name) > 255) {
            throw new InvalidArgumentException("User name cannot exceed 255 characters");
        }
    }

    // Getters
    public function getId(): UserId { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): Email { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRememberToken(): ?string { return $this->rememberToken; }
    public function getEmailVerifiedAt(): ?\DateTime { return $this->emailVerifiedAt; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    // Métodos de negocio
    public function updateProfile(string $name, Email $email): void
    {
        $this->validateName($name);
        $this->name = $name;
        $this->email = $email;
        $this->updatedAt = new \DateTime();
    }

    public function markEmailAsVerified(): void
    {
        $this->emailVerifiedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function changePassword(string $newHashedPassword): void
    {
        $this->password = $newHashedPassword;
        $this->updatedAt = new \DateTime();
    }

    // Factory method para crear nuevo usuario
    public static function create(string $name, Email $email, string $hashedPassword): self
    {
        return new self(
            UserId::generate(),
            $name,
            $email,
            $hashedPassword,
            new \DateTime(), // createdAt
            new \DateTime(), // updatedAt
            null, // rememberToken
            null  // emailVerifiedAt
        );
    }
}