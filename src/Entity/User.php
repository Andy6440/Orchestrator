<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements PasswordAuthenticatedUserInterface,UserInterface
{
    

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 255, type: 'string', unique: true)]
    private string $email;

    #[ORM\Column(length: 255, type: 'string')]
    private string $name;

    
    #[ORM\Column(length: 255, type: 'string')]
    private string $lastName;

    
    #[ORM\Column(length: 255, type: 'string')]
    private string $password;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: "users")]
    #[ORM\JoinTable(name: "user_roles")]
    private Collection $roles;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: "user", cascade: ["remove"])]
    private Collection $sessions;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->roles = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    } 

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }


    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }


    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getRoles(): array
    {
        return $this->roles->map(fn(Role $role) => $role->getCode())->toArray();
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }
        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removeUser($this);
        }
        return $this;
    }

    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): void
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
        }
    }

    public function removeSession(Session $session): void
    {
        if ($this->sessions->contains($session)) {
            $this->sessions->removeElement($session);
        }
    }

   

    public function getUserIdentifier(): string
    {
        // devolver el identificador del usuario
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }


}
