<?php

namespace Core\Users\Application\Services;

use Core\Users\Ports\UserServiceInterface;
use Core\Users\Ports\UserRepositoryInterface;
use Core\Users\Domain\User;
use Core\Users\Domain\ValueObjects\Email;
use Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createUser(array $userData): User
    {
        // Validaciones de aplicación
        $this->validateUserData($userData);

        // Verificar si el email ya existe
        $existingUser = $this->userRepository->findByEmail($userData['email']);
        if ($existingUser) {
            throw new InvalidArgumentException("User with this email already exists");
        }

        // Crear entidad de dominio
        $user = User::create(
            $userData['name'],
            new Email($userData['email']),
            Hash::make($userData['password'])
        );

        // Persistir
        $this->userRepository->save($user);

        return $user;
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById(new \Core\Users\Domain\ValueObjects\UserId($id));
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !Hash::check($password, $user->getPassword())) {
            return null;
        }

        // Obtener el modelo Eloquent para crear el token
        $eloquentUser = EloquentUser::find($user->getId()->getValue());
        
        if (!$eloquentUser) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $eloquentUser->createToken('auth-token')->plainTextToken
        ];
    }

    private function validateUserData(array $data): void
    {
        $required = ['name', 'email', 'password'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field {$field} is required");
            }
        }

        if (strlen($data['password']) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters long");
        }
    }
}