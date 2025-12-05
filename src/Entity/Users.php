<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?int $role_id = null;
    //constante pour les role : 

    public const ROLE_ADMIN = 1;
    public const ROLE_PDG = 2;
    public const ROLE_DIRECTOR = 3;
    public const ROLE_SECRETARY = 4;
    public const ROLE_PRODUCER = 5;
    public const ROLE_MINI_ADMIN = 6;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoleId(): ?int
    {
        return $this->role_id;
    }

    public function setRoleId(int $role_id): static
    {
        $this->role_id = $role_id;

        return $this;
    }


    // methodes pour les constantes de roles : 

    public function getRoles(): array
    {
        $roles = match ($this->role_id) {
            self::ROLE_ADMIN => ['ROLE_ADMIN', 'ROLE_PDG', 'ROLE_DIRECTOR', 'ROLE_SECRETARY', 'ROLE_MINI_ADMIN'],
            self::ROLE_PDG => ['ROLE_PDG'],
            self::ROLE_DIRECTOR => ['ROLE_DIRECTOR'],
            self::ROLE_SECRETARY => ['ROLE_SECRETARY'],
            self::ROLE_PRODUCER => ['ROLE_PRODUCER'],
            self::ROLE_MINI_ADMIN => ['ROLE_MINI_ADMIN'],
            default => ['ROLE_USER'],
        };

        return array_unique($roles);
    }

    public function eraseCredentials() {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoleName(): string
    {
        return match ($this->role_id) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_PDG => 'PDG',
            self::ROLE_DIRECTOR => 'Directrice',
            self::ROLE_SECRETARY => 'Secrétaire',
            self::ROLE_PRODUCER => 'Producteur',
            self::ROLE_MINI_ADMIN => 'Mini Admin',
            default => 'Utilisateur',
        };
    }
}
