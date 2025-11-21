<?php

namespace Core\Projects\Ports;

use Core\Projects\Domain\Project;

interface ProjectServiceInterface
{
    public function createProject(array $projectData, int $userId): Project;
    public function getUserProjects(int $userId): array;
    public function getProjectById(int $projectId, int $userId): ?Project;
    public function updateProject(int $projectId, array $projectData, int $userId): ?Project;
    public function updateProjectStatus(int $projectId, string $status, int $userId): ?Project;
    public function deleteProject(int $projectId, int $userId): bool;
}