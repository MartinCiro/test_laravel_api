<?php

namespace Core\Tasks\Ports;

use Core\Tasks\Domain\Task;

interface TaskServiceInterface
{
    public function createTask(array $taskData, int $projectId, int $userId): Task;
    public function getProjectTasks(int $projectId, int $userId): array;
    public function getTaskById(int $taskId, int $userId): ?Task;
    public function updateTask(int $taskId, array $taskData, int $userId): ?Task;
    public function updateTaskStatus(int $taskId, string $status, int $userId): ?Task;
    public function deleteTask(int $taskId, int $userId): bool;
}