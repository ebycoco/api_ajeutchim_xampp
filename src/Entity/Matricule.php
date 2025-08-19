<?php

namespace App\Entity;

use App\Repository\MatriculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatriculeRepository::class)]
class Matricule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'matricule', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantAdhesion = null;

    #[ORM\Column(length: 255)]
    private ?string $anneeAdhesion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commune = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quartier = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, Cotisation>
     */
    #[ORM\OneToMany(targetEntity: Cotisation::class, mappedBy: 'matricule',orphanRemoval: true)]
    private Collection $cotisations;
    
    public function __construct()
    {
        // Initialise createdAt dès qu'on instancie un nouveau Matricule
        $this->createdAt = new \DateTimeImmutable();
        $this->cotisations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setMatricule(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getMatricule() !== $this) {
            $user->setMatricule($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getMontantAdhesion(): ?string
    {
        return $this->montantAdhesion;
    }

    public function setMontantAdhesion(string $montantAdhesion): static
    {
        $this->montantAdhesion = $montantAdhesion;

        return $this;
    }

    public function getAnneeAdhesion(): ?string
    {
        return $this->anneeAdhesion;
    }

    public function setAnneeAdhesion(string $anneeAdhesion): static
    {
        $this->anneeAdhesion = $anneeAdhesion;

        return $this;
    }

     public function getCommune(): ?string
    {
        return $this->commune;
    }

    public function setCommune(?string $commune): static
    {
        $this->commune = $commune;
        return $this;
    }

    public function getQuartier(): ?string
    {
        return $this->quartier;
    }

    public function setQuartier(?string $quartier): static
    {
        $this->quartier = $quartier;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): static
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

     public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Cotisation>
     */
    public function getCotisations(): Collection
    {
        return $this->cotisations;
    }

    public function addCotisation(Cotisation $cotisation): static
    {
        if (!$this->cotisations->contains($cotisation)) {
            $this->cotisations->add($cotisation);
            $cotisation->setMatricule($this);
        }

        return $this;
    }

    public function removeCotisation(Cotisation $cotisation): static
    {
        if ($this->cotisations->removeElement($cotisation)) {
            // set the owning side to null (unless already changed)
            if ($cotisation->getMatricule() === $this) {
                $cotisation->setMatricule(null);
            }
        }

        return $this;
    }
}
