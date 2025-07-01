<?php

namespace App\Entity;

use App\Repository\ImporterFileDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImporterFileDataRepository::class)]
class ImporterFileData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfProducts = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfNotFoundProducts = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfProductsExcludedByStatus = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfProductsStockNotChanged = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getNumberOfProducts(): ?int
    {
        return $this->numberOfProducts;
    }

    public function setNumberOfProducts(?int $numberOfProducts): static
    {
        $this->numberOfProducts = $numberOfProducts;

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

    public function getNumberOfNotFoundProducts(): ?int
    {
        return $this->numberOfNotFoundProducts;
    }

    public function setNumberOfNotFoundProducts(?int $numberOfNotFoundProducts): static
    {
        $this->numberOfNotFoundProducts = $numberOfNotFoundProducts;

        return $this;
    }

    public function getNumberOfProductsExcludedByStatus(): ?int
    {
        return $this->numberOfProductsExcludedByStatus;
    }

    public function setNumberOfProductsExcludedByStatus(?int $numberOfProductsExcludedByStatus): static
    {
        $this->numberOfProductsExcludedByStatus = $numberOfProductsExcludedByStatus;

        return $this;
    }

    public function getNumberOfProductsStockNotChanged(): ?int
    {
        return $this->numberOfProductsStockNotChanged;
    }

    public function setNumberOfProductsStockNotChanged(?int $numberOfProductsStockNotChanged): static
    {
        $this->numberOfProductsStockNotChanged = $numberOfProductsStockNotChanged;

        return $this;
    }
}
