<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: '"user"')]
#[UniqueEntity(fields: ['username'], message: 'Ya existe una cuenta con ese email.')]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type:'string', length: 50, unique: true)]
    private string $username;

    #[ORM\Column(type:'string')]
    private string $password;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $lastPull = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $pullsUsedToday = 0;

    public function getLastPull(): ?string
    {
        return $this->lastPull;
    }

    public function setLastPull(?string $lastPull): void
    {
        $this->lastPull = $lastPull;
    }

    public function getPullsUsedToday(): int
    {
        return $this->pullsUsedToday;
    }

    public function setPullsUsedToday(int $pullsUsedToday): void
    {
        $this->pullsUsedToday = $pullsUsedToday;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }
}
