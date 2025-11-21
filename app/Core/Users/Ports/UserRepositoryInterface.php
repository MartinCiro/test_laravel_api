<?php

namespace Core\Users\Ports;

use Core\Users\Domain\User;
use Core\Users\Domain\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function delete(UserId $id): bool;
}