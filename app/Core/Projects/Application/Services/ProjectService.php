<?php

namespace Core\Projects\Application\Services;

use Core\Projects\Ports\ProjectServiceInterface;
use Core\Projects\Ports\ProjectRepositoryInterface;
use Core\Projects\Domain\Project;
use Core\Projects\Domain\Enums\ProjectStatus;
use Core\Projects\Domain\ValueObjects\ProjectId;
use Core\Users\Domain\ValueObjects\UserId;
use InvalidArgumentException;

class ProjectService implements ProjectServiceInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {}

    public function createProject(array $projectData, int $userId): Project
    {
        $this->validateProjectData($projectData);

        $project = Project::create(
            $projectData['name'],
            $projectData['description'] ?? null,
            $userId
        );

        $this->projectRepository->save($project);

        return $project;
    }

    public function getUserProjects(int $userId): array
    {
        return $this->projectRepository->findByUserId($userId);
    }

    public function getProjectById(int $projectId, int $userId): ?Project
    {
        $project = $this->projectRepository->findById(new ProjectId($projectId));
        
        if (!$project || $project->getUserId()->getValue() !== $userId) {
            return null;
        }

        return $project;
    }

    public function updateProject(int $projectId, array $projectData, int $userId): ?Project
    {
        $project = $this->getProjectById($projectId, $userId);
        
        if (!$project) {
            return null;
        }

        $this->validateProjectData($projectData);

        $project->update(
            $projectData['name'],
            $projectData['description'] ?? null
        );

        $this->projectRepository->save($project);

        return $project;
    }

    public function updateProjectStatus(int $projectId, string $status, int $userId): ?Project
    {
        $project = $this->getProjectById($projectId, $userId);
        
        if (!$project) {
            return null;
        }

        $projectStatus = ProjectStatus::tryFrom($status);
        if (!$projectStatus) {
            throw new InvalidArgumentException("Invalid project status: {$status}");
        }

        $project->changeStatus($projectStatus);
        $this->projectRepository->save($project);

        return $project;
    }

    public function deleteProject(int $projectId, int $userId): bool
    {
        $project = $this->getProjectById($projectId, $userId);
        
        if (!$project) {
            return false;
        }

        return $this->projectRepository->delete($project->getId());
    }

    private function validateProjectData(array $data): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException("Project name is required");
        }

        if (strlen($data['name']) < 3) {
            throw new InvalidArgumentException("Project name must be at least 3 characters long");
        }

        if (strlen($data['name']) > 255) {
            throw new InvalidArgumentException("Project name cannot exceed 255 characters");
        }
    }
}