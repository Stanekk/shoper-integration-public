<?php

namespace App\Entity;

use App\Repository\ShoperConnectionSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShoperConnectionSettingsRepository::class)]
class ShoperConnectionSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $restUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $restPassword = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shopUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRestUser(): ?string
    {
        return $this->restUser;
    }

    public function setRestUser(?string $restUser): static
    {
        $this->restUser = $restUser;

        return $this;
    }

    public function getRestPassword(): ?string
    {
        return $this->restPassword;
    }

    public function setRestPassword(?string $restPassword): static
    {
        $this->restPassword = $restPassword;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getShopUrl(): ?string
    {
        return $this->shopUrl;
    }

    public function setShopUrl(?string $shopUrl): static
    {
        $this->shopUrl = $shopUrl;

        return $this;
    }
}
