<?php

namespace App\Entity;

use App\Repository\ValidatorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ValidatorRepository::class)]
class Validator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $smtp_status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $list = null;

    #[ORM\Column(nullable: true)]
    private ?bool $multi_mailing = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $priority = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSmtpStatus(): ?string
    {
        return $this->smtp_status;
    }

    public function setSmtpStatus(string $smtp_status): static
    {
        $this->smtp_status = $smtp_status;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getList(): ?string
    {
        return $this->list;
    }

    public function setList(?string $list): static
    {
        $this->list = $list;

        return $this;
    }

    public function isMultiMailing(): ?bool
    {
        return $this->multi_mailing;
    }

    public function setMultiMailing(?bool $multi_mailing): static
    {
        $this->multi_mailing = $multi_mailing;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }
}
