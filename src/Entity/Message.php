<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['conv:read','msg:read'])]
    private ?int $id = null;

    #[Groups(['msg:read'])]
    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[Groups(['msg:read','msg:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private string $envoyeurId;

    #[Groups(['msg:read','msg:write'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['msg:read'])]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['msg:read'])]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['msg:read'])]
    private ?\DateTimeImmutable $readAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getEnvoyeurId(): ?string
    {
        return $this->envoyeurId;
    }

    public function setEnvoyeurId(?string $envoyeurId): static
    {
        $this->envoyeurId = $envoyeurId;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

        return $this;
    }
}
