<?php

namespace Core\Tasks\Domain;

use Core\Tasks\Domain\ValueObjects\TaskId;
use Core\Tasks\Domain\Enums\TaskStatus;
use Core\Projects\Domain\ValueObjects\ProjectId;
use Core\Users\Domain\ValueObjects\UserId;
use InvalidArgumentException;

class Task
{
    public function __construct(
        private TaskId $id,
        private string $title,
        private ?string $description,
        private TaskStatus $status,
        private ?\DateTime $dueDate,
        private ProjectId $projectId,
        private UserId $userId,
        private \DateTime $createdAt,
        private \DateTime $updatedAt
    ) {
        $this->validateTitle($title);
    }

    private function validateTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new InvalidArgumentException("Task title cannot be empty");
        }

        if (strlen($title) < 3) {
            throw new InvalidArgumentException("Task title must be at least 3 characters long");
        }

        if (strlen($title) > 255) {
            throw new InvalidArgumentException("Task title cannot exceed 255 characters");
        }
    }

    // Getters
    public function getId(): TaskId { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): TaskStatus { return $this->status; }
    public function getDueDate(): ?\DateTime { return $this->dueDate; }
    public function getProjectId(): ProjectId { return $this->projectId; }
    public function getUserId(): UserId { return $this->userId; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    // Métodos de negocio
    public function update(string $title, ?string $description, ?\DateTime $dueDate): void
    {
        $this->validateTitle($title);
        $this->title = $title;
        $this->description = $description;
        $this->dueDate = $dueDate;
        $this->updatedAt = new \DateTime();
    }

    public function changeStatus(TaskStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
    }

    // Factory method
    public static function create(string $title, ?string $description, ?\DateTime $dueDate, int $projectId, int $userId): self
    {
        return new self(
            TaskId::generate(),
            $title,
            $description,
            TaskStatus::TODO,
            $dueDate,
            new ProjectId($projectId),
            new UserId($userId),
            new \DateTime(),
            new \DateTime()
        );
    }
}