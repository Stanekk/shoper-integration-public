<?php

namespace App\Entity;

use App\Repository\PublisherRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublisherRepository::class)]
class Publisher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $shoperId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'publishers')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Wholesaler $wholesaler = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShoperId(): ?int
    {
        return $this->shoperId;
    }

    public function setShoperId(?int $shoperId): static
    {
        $this->shoperId = $shoperId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getWholesaler(): ?Wholesaler
    {
        return $this->wholesaler;
    }

    public function setWholesaler(?Wholesaler $wholesaler): static
    {
        $this->wholesaler = $wholesaler;

        return $this;
    }
}
