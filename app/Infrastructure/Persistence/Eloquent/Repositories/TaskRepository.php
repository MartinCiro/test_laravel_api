<?php

namespace Infrastructure\Persistence\Eloquent\Repositories;

use Core\Tasks\Ports\TaskRepositoryInterface;
use Core\Tasks\Domain\Task as DomainTask;
use Core\Tasks\Domain\ValueObjects\TaskId;
use Core\Tasks\Domain\Enums\TaskStatus;
use Core\Projects\Domain\ValueObjects\ProjectId;
use Core\Users\Domain\ValueObjects\UserId;
use Infrastructure\Persistence\Eloquent\Models\Task as EloquentTask;
use DateTime;

class TaskRepository implements TaskRepositoryInterface
{
    public function findById(TaskId $id): ?DomainTask
    {
        $task = EloquentTask::find($id->getValue());
        
        if (!$task) {
            return null;
        }

        return $this->toDomainEntity($task);
    }

    public function findByProjectId(int $projectId): array
    {
        return EloquentTask::where('project_id', $projectId)
            ->get()
            ->map(function ($task) {
                return $this->toDomainEntity($task);
            })
            ->toArray();
    }

    public function save(DomainTask $domainTask): void
    {
        $taskData = [
            'title' => $domainTask->getTitle(),
            'description' => $domainTask->getDescription(),
            'status' => $domainTask->getStatus()->value,
            'due_date' => $domainTask->getDueDate(),
            'project_id' => $domainTask->getProjectId()->getValue(),
            'user_id' => $domainTask->getUserId()->getValue(),
            'created_at' => $domainTask->getCreatedAt(),
            'updated_at' => $domainTask->getUpdatedAt(),
        ];

        if ($domainTask->getId()->getValue() < 1000000) {
            $task = EloquentTask::create($taskData);
            
            // Actualizar el ID de la entidad de dominio
            $reflection = new \ReflectionClass($domainTask);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($domainTask, new TaskId($task->id));
        } else {
            EloquentTask::where('id', $domainTask->getId()->getValue())
                        ->update($taskData);
        }
    }

    public function delete(TaskId $id): bool
    {
        return EloquentTask::destroy($id->getValue()) > 0;
    }

    private function toDomainEntity(EloquentTask $task): DomainTask
    {
        return new DomainTask(
            new TaskId($task->id),
            $task->title,
            $task->description,
            TaskStatus::from($task->status),
            $task->due_date ? new DateTime($task->due_date) : null,
            new ProjectId($task->project_id),
            new UserId($task->user_id),
            new DateTime($task->created_at),
            new DateTime($task->updated_at)
        );
    }
}