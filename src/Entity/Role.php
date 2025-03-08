<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{

    public function __construct()
    {
        $this->createdAt = Carbon::now();
        $this->updatedAt = Carbon::now();
        $this->users = new ArrayCollection();
    }
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(length: 255, type: 'string')]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    #[ORM\Column(length: 255, type: 'string')]
    private string $code;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: "roles")]
    private Collection $users;

    public function getUsers(): Collection
    {
        return $this->users;
    }


    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addRole($this); // Relación bidireccional
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeRole($this);
        }
        return $this;
    }

    #[ORM\Column(type: 'datetime')]
    private Carbon $createdAt;

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    #[ORM\Column(type: 'datetime')]
    private Carbon $updatedAt;


    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(Carbon $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
