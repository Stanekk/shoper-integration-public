<?php

namespace App\Entity;

use App\Repository\ImportProductsStatsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportProductsStatsRepository::class)]
class ImportProductsStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalProducts = null;

    #[ORM\Column(nullable: true)]
    private ?int $noEan = null;

    #[ORM\Column(nullable: true)]
    private ?int $updated = null;

    #[ORM\Column(nullable: true)]
    private ?float $executionTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $new = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalProducts(): ?int
    {
        return $this->totalProducts;
    }

    public function setTotalProducts(?int $totalProducts): static
    {
        $this->totalProducts = $totalProducts;

        return $this;
    }

    public function getNoEan(): ?int
    {
        return $this->noEan;
    }

    public function setNoEan(?int $noEan): static
    {
        $this->noEan = $noEan;

        return $this;
    }

    public function getUpdated(): ?int
    {
        return $this->updated;
    }

    public function setUpdated(int $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(?float $executionTime): static
    {
        $this->executionTime = $executionTime;

        return $this;
    }

    public function getNew(): ?int
    {
        return $this->new;
    }

    public function setNew(?int $new): static
    {
        $this->new = $new;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
