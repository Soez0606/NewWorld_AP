<?php

namespace App\Entity;

use App\Repository\ProducersInfoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProducersInfoRepository::class)]
class ProducersInfo
{
   #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom du responsable est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/',
        message: 'Le nom ne doit contenir que des lettres, des espaces ou des tirets.'
    )]
    private ?string $contact_name = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire.')]
    #[Assert\Length(min: 10, minMessage: 'L\'adresse semble trop courte.')]
    private ?string $address = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/',
        message: 'Le format du numéro de téléphone n\'est pas valide (ex: 0612345678).'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 14)]
    #[Assert\NotBlank(message: 'Le SIRET est obligatoire.')]
    #[Assert\Length(
        exactly: 14,
        exactMessage: 'Le SIRET doit contenir exactement 14 caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9]{14}$/',
        message: 'Le SIRET ne doit contenir QUE des chiffres, sans espaces.'
    )]
    private ?string $siret = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Une description de votre activité est requise.')]
    #[Assert\Length(
        min: 20,
        minMessage: 'Votre description doit faire au moins 20 caractères pour être étudiée par la direction.'
    )]
    private ?string $activity = null;

    #[ORM\Column]
    private ?\DateTime $registration_date = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $validation_audit_date = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $termination_date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $archived = null;

    #[ORM\Column(length: 255)]
    private ?string $status_audit = null;

    //cstatus roles constantes 

    public const STATUS_PENDING = 'pending';
    public const STATUS_AUDIT_REQUIRED = 'audit_required';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AUDIT_REJECTED = 'audit_rejected';


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

      public function getContactName(): ?string
    {
        return $this->contact_name;
    }

    public function setContactName(string $contact_name): static
    {
        $this->contact_name = $contact_name;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->activity;
    }

    public function setActivity(string $activity): static
    {
        $this->activity = $activity;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTime
    {
        return $this->registration_date;
    }

    public function setRegistrationDate(\DateTime $registration_date): static
    {
        $this->registration_date = $registration_date;

        return $this;
    }

    public function getValidationAuditDate(): ?\DateTime
    {
        return $this->validation_audit_date;
    }

    public function setValidationAuditDate(?\DateTime $validation_audit_date): static
    {
        $this->validation_audit_date = $validation_audit_date;

        return $this;
    }

    public function getTerminationDate(): ?\DateTime
    {
        return $this->termination_date;
    }

    public function setTerminationDate(?\DateTime $termination_date): static
    {
        $this->termination_date = $termination_date;

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(?bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    public function getStatusAudit(): ?string
    {
        return $this->status_audit;
    }

    public function setStatusAudit(string $status_audit): static
    {
        $this->status_audit = $status_audit;

        return $this;
    }

    //getters setters pour les roles 

      public function isPending(): bool
    {
        return $this->status_audit === self::STATUS_PENDING;
    }

    public function requiresAudit(): bool
    {
        return $this->status_audit === self::STATUS_AUDIT_REQUIRED;
    }

    public function isApproved(): bool
    {
        return $this->status_audit === self::STATUS_APPROVED;
    }
    
    public function getStatusLabel(): string
    {
        return match($this->status_audit) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_AUDIT_REQUIRED => 'Audit requis',
            self::STATUS_APPROVED => 'Validé',
            self::STATUS_REJECTED => 'Refusé',
            self::STATUS_AUDIT_REJECTED => 'Audit refusé',
            default => 'Inconnu',
        };
    }
}
