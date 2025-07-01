<?php

namespace App\Service;

use App\Entity\Publisher;
use App\Entity\Wholesaler;
use App\Exception\ApiException;
use App\Exception\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class WholesalersService
{

    private $entityManager;
    private $publishersService;

    public function __construct(EntityManagerInterface $entityManager,PublishersService $publishersService)
    {
        $this->entityManager = $entityManager;
        $this->publishersService = $publishersService;
    }

    public function getWholesaler($id)
    {
        return $this->entityManager->getRepository(Wholesaler::class)->find($id);
    }

    public function getWholesalers(): array
    {
        return $this->entityManager->getRepository(Wholesaler::class)->findAll();
    }

    /**
     * @throws \Exception
     */
    public function saveWholesaler(Wholesaler $wholesaler): void
    {
        if ($wholesaler->getName()) {
            $wholesaler->setCreatedAt(new \DateTimeImmutable('now'));
            $this->entityManager->persist($wholesaler);
            $this->entityManager->flush();
        } else {
            throw new \Exception('Invalid Wholesaler');
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    public function removeWholesalerById(int $id): void
    {
        $wholesaler = $this->entityManager->getRepository(Wholesaler::class)->find($id);
        if(!$wholesaler) {
            throw new EntityNotFoundException($id);
        }
        $this->entityManager->remove($wholesaler);
        $this->entityManager->flush();
    }

    /**
     * @throws ApiException
     * @throws EntityNotFoundException
     */
    public function assignPublishersToWholesaler(Wholesaler $wholesaler,$publishersIds): void
    {
        if(!is_array($publishersIds)) {
            throw new ApiException('Publishers list is invalid');
        }
        $assignedPublishers = $wholesaler->getPublishers();
        foreach ($assignedPublishers as $assignedPublisher) {
            if(!in_array($assignedPublisher->getId(), $publishersIds)) {
                $assignedPublisher->setWholesaler(null);
            }
        }
        foreach ($publishersIds as $publisherId) {
            $publisher = $this->publishersService->getPublisherById($publisherId);
            if(!$publisher) {
                throw new EntityNotFoundException($publisherId);
            }
            $publisher->setWholesaler($wholesaler);
            $this->entityManager->persist($publisher);
        }
        $this->entityManager->flush();
    }
}