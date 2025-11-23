<?php

namespace Core\Tasks\Ports;

use Core\Tasks\Domain\Task;
use Core\Tasks\Domain\ValueObjects\TaskId;

interface TaskRepositoryInterface
{
    public function findById(TaskId $id): ?Task;
    public function findByProjectId(int $projectId): array;
    public function save(Task $task): void;
    public function delete(TaskId $id): bool;
}