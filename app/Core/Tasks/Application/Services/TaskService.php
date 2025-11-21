<?php

namespace Core\Tasks\Application\Services;

use Core\Tasks\Ports\TaskServiceInterface;
use Core\Tasks\Ports\TaskRepositoryInterface;
use Core\Tasks\Domain\Task;
use Core\Tasks\Domain\Enums\TaskStatus;
use Core\Tasks\Domain\ValueObjects\TaskId;
use Core\Projects\Domain\ValueObjects\ProjectId;
use Core\Users\Domain\ValueObjects\UserId;
use InvalidArgumentException;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}

    public function createTask(array $taskData, int $projectId, int $userId): Task
    {
        $this->validateTaskData($taskData);

        $dueDate = isset($taskData['due_date']) ? new \DateTime($taskData['due_date']) : null;

        $task = Task::create(
            $taskData['title'],
            $taskData['description'] ?? null,
            $dueDate,
            $projectId,
            $userId
        );

        $this->taskRepository->save($task);

        return $task;
    }

    public function getProjectTasks(int $projectId, int $userId): array
    {
        return $this->taskRepository->findByProjectId($projectId);
    }

    public function getTaskById(int $taskId, int $userId): ?Task
    {
        $task = $this->taskRepository->findById(new TaskId($taskId));
        
        if (!$task || $task->getUserId()->getValue() !== $userId) {
            return null;
        }

        return $task;
    }

    public function updateTask(int $taskId, array $taskData, int $userId): ?Task
    {
        $task = $this->getTaskById($taskId, $userId);
        
        if (!$task) {
            return null;
        }

        $this->validateTaskData($taskData);

        $dueDate = isset($taskData['due_date']) ? new \DateTime($taskData['due_date']) : null;

        $task->update(
            $taskData['title'],
            $taskData['description'] ?? null,
            $dueDate
        );

        $this->taskRepository->save($task);

        return $task;
    }

    public function updateTaskStatus(int $taskId, string $status, int $userId): ?Task
    {
        $task = $this->getTaskById($taskId, $userId);
        
        if (!$task) {
            return null;
        }

        $taskStatus = TaskStatus::tryFrom($status);
        if (!$taskStatus) {
            throw new InvalidArgumentException("Invalid task status: {$status}");
        }

        $task->changeStatus($taskStatus);
        $this->taskRepository->save($task);

        return $task;
    }

    public function deleteTask(int $taskId, int $userId): bool
    {
        $task = $this->getTaskById($taskId, $userId);
        
        if (!$task) {
            return false;
        }

        return $this->taskRepository->delete($task->getId());
    }

    private function validateTaskData(array $data): void
    {
        if (empty($data['title'])) {
            throw new InvalidArgumentException("Task title is required");
        }

        if (strlen($data['title']) < 3) {
            throw new InvalidArgumentException("Task title must be at least 3 characters long");
        }

        if (strlen($data['title']) > 255) {
            throw new InvalidArgumentException("Task title cannot exceed 255 characters");
        }

        // Validar fecha si existe
        if (isset($data['due_date']) && !strtotime($data['due_date'])) {
            throw new InvalidArgumentException("Invalid due date format");
        }
    }
}