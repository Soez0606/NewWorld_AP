<?php

namespace App\Entity;

use App\Repository\ContractsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractsRepository::class)]
class Contracts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column]
    private ?\DateTime $signature_date = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $expiration_date = null;

    #[ORM\Column]
    private ?int $notice_months = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $activity_description = null;

   
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

    public function getSignatureDate(): ?\DateTime
    {
        return $this->signature_date;
    }

    public function setSignatureDate(\DateTime $signature_date): static
    {
        $this->signature_date = $signature_date;

        return $this;
    }

    public function getExpirationDate(): ?\DateTime
    {
        return $this->expiration_date;
    }

    public function setExpirationDate(?\DateTime $expiration_date): static
    {
        $this->expiration_date = $expiration_date;

        return $this;
    }

    public function getNoticeMonths(): ?int
    {
        return $this->notice_months;
    }

    public function setNoticeMonths(int $notice_months): static
    {
        $this->notice_months = $notice_months;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
     public function getActivityDescription(): ?string
    {
        return $this->activity_description;
    }

    public function setActivityDescription(?string $activity_description): static
    {
        $this->activity_description = $activity_description;

        return $this;
    }

}
