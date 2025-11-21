<?php

namespace Core\Projects\Ports;

use Core\Projects\Domain\Project;
use Core\Projects\Domain\ValueObjects\ProjectId;

interface ProjectRepositoryInterface
{
    public function findById(ProjectId $id): ?Project;
    public function findByUserId(int $userId): array;
    public function save(Project $project): void;
    public function delete(ProjectId $id): bool;
}