<?php

namespace Infrastructure\Persistence\Eloquent\Repositories;

use Core\Users\Ports\UserRepositoryInterface;
use Core\Users\Domain\User as DomainUser;
use Core\Users\Domain\ValueObjects\UserId;
use Core\Users\Domain\ValueObjects\Email;
use Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use DateTime;

class UserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?DomainUser
    {
        $user = EloquentUser::find($id->getValue());
        
        if (!$user) {
            return null;
        }

        return $this->toDomainEntity($user);
    }

    public function findByEmail(string $email): ?DomainUser
    {
        $user = EloquentUser::where('email', $email)->first();
        
        if (!$user) {
            return null;
        }

        return $this->toDomainEntity($user);
    }

    public function save(DomainUser $domainUser): void
    {
        $userData = [
            'name' => $domainUser->getName(),
            'email' => $domainUser->getEmail()->getValue(),
            'password' => $domainUser->getPassword(),
            'remember_token' => $domainUser->getRememberToken(),
            'email_verified_at' => $domainUser->getEmailVerifiedAt(),
            'created_at' => $domainUser->getCreatedAt(),
            'updated_at' => $domainUser->getUpdatedAt(),
        ];

        // Si es un usuario nuevo (ID temporal), crear
        if ($domainUser->getId()->getValue() < 1000000) {
            $user = EloquentUser::create($userData);
            
            // Actualizar el ID de la entidad de dominio con el ID real de la BD
            $reflection = new \ReflectionClass($domainUser);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($domainUser, new UserId($user->id));
        } else {
            // Actualizar usuario existente
            EloquentUser::where('id', $domainUser->getId()->getValue())
                        ->update($userData);
        }
    }

    public function delete(UserId $id): bool
    {
        return EloquentUser::destroy($id->getValue()) > 0;
    }

    private function toDomainEntity(EloquentUser $user): DomainUser
    {
        return new DomainUser(
            new UserId($user->id),
            $user->name,
            new Email($user->email),
            $user->password,
            new DateTime($user->created_at),
            new DateTime($user->updated_at),
            $user->remember_token,
            $user->email_verified_at ? new DateTime($user->email_verified_at) : null
        );
    }
}