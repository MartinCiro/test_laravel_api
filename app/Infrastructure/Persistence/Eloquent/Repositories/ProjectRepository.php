<?php

namespace Infrastructure\Persistence\Eloquent\Repositories;

use Core\Projects\Ports\ProjectRepositoryInterface;
use Core\Projects\Domain\Project as DomainProject;
use Core\Projects\Domain\ValueObjects\ProjectId;
use Core\Projects\Domain\Enums\ProjectStatus;
use Core\Users\Domain\ValueObjects\UserId;
use Infrastructure\Persistence\Eloquent\Models\Project as EloquentProject;
use DateTime;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findById(ProjectId $id): ?DomainProject
    {
        $project = EloquentProject::find($id->getValue());
        
        if (!$project) {
            return null;
        }

        return $this->toDomainEntity($project);
    }

    public function findByUserId(int $userId): array
    {
        return EloquentProject::where('user_id', $userId)
            ->get()
            ->map(function ($project) {
                return $this->toDomainEntity($project);
            })
            ->toArray();
    }

    public function save(DomainProject $domainProject): void
    {
        $projectData = [
            'name' => $domainProject->getName(),
            'description' => $domainProject->getDescription(),
            'status' => $domainProject->getStatus()->value,
            'user_id' => $domainProject->getUserId()->getValue(),
            'created_at' => $domainProject->getCreatedAt(),
            'updated_at' => $domainProject->getUpdatedAt(),
        ];

        if ($domainProject->getId()->getValue() < 1000000) {
            $project = EloquentProject::create($projectData);
            
            // Actualizar el ID de la entidad de dominio
            $reflection = new \ReflectionClass($domainProject);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($domainProject, new ProjectId($project->id));
        } else {
            EloquentProject::where('id', $domainProject->getId()->getValue())
                          ->update($projectData);
        }
    }

    public function delete(ProjectId $id): bool
    {
        return EloquentProject::destroy($id->getValue()) > 0;
    }

    private function toDomainEntity(EloquentProject $project): DomainProject
    {
        return new DomainProject(
            new ProjectId($project->id),
            $project->name,
            $project->description,
            ProjectStatus::from($project->status),
            new UserId($project->user_id),
            new DateTime($project->created_at),
            new DateTime($project->updated_at)
        );
    }
}