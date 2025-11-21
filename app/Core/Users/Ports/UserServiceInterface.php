<?php

namespace Core\Users\Ports;

use Core\Users\Domain\User;

interface UserServiceInterface
{
    public function createUser(array $userData): User;
    public function getUserById(int $id): ?User;
    public function authenticate(string $email, string $password): ?array;
}