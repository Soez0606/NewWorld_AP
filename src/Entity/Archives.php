<?php

namespace App\Entity;

use App\Repository\ArchivesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArchivesRepository::class)]
class Archives
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column]
    private ?\DateTime $archive_date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reason = null;

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

    public function getArchiveDate(): ?\DateTime
    {
        return $this->archive_date;
    }

    public function setArchiveDate(\DateTime $archive_date): static
    {
        $this->archive_date = $archive_date;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
