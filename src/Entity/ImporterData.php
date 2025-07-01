<?php

namespace App\Entity;

use App\Repository\ImporterDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImporterDataRepository::class)]
class ImporterData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePublisher = null;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(nullable: true)]
    private ?int $oldStock = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permalink = null;

    #[ORM\Column(nullable: true)]
    private ?int $shoperProductId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fromFile = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isExecuted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): static
    {
        $this->ean = $ean;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getFilePublisher(): ?string
    {
        return $this->filePublisher;
    }

    public function setFilePublisher(?string $publisher): static
    {
        $this->filePublisher = $publisher;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getOldStock(): ?int
    {
        return $this->oldStock;
    }

    public function setOldStock(?int $stock): static
    {
        $this->oldStock = $stock;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getShoperProductId(): ?int
    {
        return $this->shoperProductId;
    }

    public function setShoperProductId(?int $shoperProductId): static
    {
        $this->shoperProductId = $shoperProductId;

        return $this;
    }

    public function getPermalink(): ?string
    {
        return $this->permalink;
    }

    public function setPermalink(?string $permalink): static
    {
        $this->permalink = $permalink;

        return $this;
    }

    public function getFromFile(): ?string
    {
        return $this->fromFile;
    }

    public function setFromFile(?string $fromFile): static
    {
        $this->fromFile = $fromFile;

        return $this;
    }

    public function isExecuted(): ?bool
    {
        return $this->isExecuted;
    }

    public function setExecuted(?bool $isExecuted): static
    {
        $this->isExecuted = $isExecuted;

        return $this;
    }
}
