<?php

namespace App\Entity;

use App\Repository\WholesalerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: WholesalerRepository::class)]
#[UniqueEntity('name',message:"A wholesaler with that name already exists")]
class Wholesaler
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Publisher>
     */
    #[ORM\OneToMany(targetEntity: Publisher::class, mappedBy: 'wholesaler')]
    private Collection $publishers;

    public function __construct()
    {
        $this->publishers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Publisher>
     */
    public function getPublishers(): Collection
    {
        return $this->publishers;
    }

    public function addPublisher(Publisher $publisher): static
    {
        if (!$this->publishers->contains($publisher)) {
            $this->publishers->add($publisher);
            $publisher->setWholesaler($this);
        }

        return $this;
    }

    public function removePublisher(Publisher $publisher): static
    {
        if ($this->publishers->removeElement($publisher)) {
            // set the owning side to null (unless already changed)
            if ($publisher->getWholesaler() === $this) {
                $publisher->setWholesaler(null);
            }
        }

        return $this;
    }

}
