<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\EventRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['event:read', 'task:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['event:read'])]
    private Task $task;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['event:read', 'task:read'])]
    private string $eventType;

    #[ORM\Column(type: 'json')]
    #[Groups(['event:read', 'task:read'])]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable', options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups(['event:read', 'task:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct(Task $task, string $eventType, array $payload)
    {
        $this->task = $task;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Métodos Getters y Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function setTask(Task $task): self
    {
        $this->task = $task;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
