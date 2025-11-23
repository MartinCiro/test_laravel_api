<?php

namespace Core\Projects\Domain;

use Core\Projects\Domain\ValueObjects\ProjectId;
use Core\Projects\Domain\Enums\ProjectStatus;
use Core\Users\Domain\ValueObjects\UserId;
use InvalidArgumentException;

class Project
{
    public function __construct(
        private ProjectId $id,
        private string $name,
        private ?string $description,
        private ProjectStatus $status,
        private UserId $userId,
        private \DateTime $createdAt,
        private \DateTime $updatedAt
    ) {
        $this->validateName($name);
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException("Project name cannot be empty");
        }

        if (strlen($name) < 3) {
            throw new InvalidArgumentException("Project name must be at least 3 characters long");
        }

        if (strlen($name) > 255) {
            throw new InvalidArgumentException("Project name cannot exceed 255 characters");
        }
    }

    // Getters
    public function getId(): ProjectId { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): ProjectStatus { return $this->status; }
    public function getUserId(): UserId { return $this->userId; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    // Métodos de negocio
    public function update(string $name, ?string $description): void
    {
        $this->validateName($name);
        $this->name = $name;
        $this->description = $description;
        $this->updatedAt = new \DateTime();
    }

    public function changeStatus(ProjectStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
    }

    // Factory method
    public static function create(string $name, ?string $description, int $userId): self
    {
        return new self(
            ProjectId::generate(),
            $name,
            $description,
            ProjectStatus::PENDING,
            new UserId($userId),
            new \DateTime(),
            new \DateTime()
        );
    }
}