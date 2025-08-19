<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['conv:read', 'conv:write'])]
    #[ORM\Column]
    private array $participantsId = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipientId = null;

    #[Groups(['conv:read','conv:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $nameParticipant = null;

    #[ORM\Column(length: 255)]
    private ?string $nameRecipient = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastMessage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastDate = null;

    #[ORM\Column(length: 255)]
    private ?string $messageStatus = 'sent';

    #[ORM\Column(nullable: true)]
    private ?int $unreadCount = 0;

    #[ORM\Column]
    private ?bool $online = false;

    #[ORM\Column]
    private ?bool $newConversation = true;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation')]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipantsId(): array
    {
        return $this->participantsId;
    }

    public function setParticipantsId(array $participantsId): static
    {
        $this->participantsId = $participantsId;

        return $this;
    }

    public function getRecipientId(): ?string
    {
        return $this->recipientId;
    }

    public function setRecipientId(?string $recipientId): static
    {
        $this->recipientId = $recipientId;

        return $this;
    }

    public function getNameParticipant(): ?string
    {
        return $this->nameParticipant;
    }

    public function setNameParticipant(string $nameParticipant): static
    {
        $this->nameParticipant = $nameParticipant;

        return $this;
    }

    public function getNameRecipient(): ?string
    {
        return $this->nameRecipient;
    }

    public function setNameRecipient(?string $nameRecipient): static
    {
        $this->nameRecipient = $nameRecipient;

        return $this;
    }

    public function getLastMessage(): ?string
    {
        return $this->lastMessage;
    }

    public function setLastMessage(?string $lastMessage): static
    {
        $this->lastMessage = $lastMessage;

        return $this;
    }

    public function getLastDate(): ?\DateTimeImmutable
    {
        return $this->lastDate;
    }

    public function setLastDate(\DateTimeImmutable $lastDate): static
    {
        $this->lastDate = $lastDate;

        return $this;
    }

    public function getMessageStatus(): ?string
    {
        return $this->messageStatus;
    }

    public function setMessageStatus(string $messageStatus): static
    {
        $this->messageStatus = $messageStatus;

        return $this;
    }

    public function getUnreadCount(): ?int
    {
        return $this->unreadCount;
    }

    public function setUnreadCount(?int $unreadCount): static
    {
        $this->unreadCount = $unreadCount;

        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): static
    {
        $this->online = $online;

        return $this;
    }

    public function isNewConversation(): ?bool
    {
        return $this->newConversation;
    }

    public function setNewConversation(bool $newConversation): static
    {
        $this->newConversation = $newConversation;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }
}
