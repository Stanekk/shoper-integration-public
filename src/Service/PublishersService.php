<?php

namespace App\Service;

use App\Api\ShoperConnectionApi;
use App\Entity\Publisher;
use App\Exception\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PublishersService
{
    private $entityManager;
    private $apiClient;

    public function __construct(EntityManagerInterface $entityManager,ShoperConnectionApi $apiClient)
    {
        $this->entityManager = $entityManager;
        $this->apiClient = $apiClient;
    }

    public function getPublisherById(int $publisherId): Publisher
    {
        return $this->entityManager->getRepository(Publisher::class)->find($publisherId);
    }

    public function getPublishers()
    {
        return $this->entityManager->getRepository(Publisher::class)->findAll();
    }

    public function removeAllPublishersFromDatabase()
    {
        $publishers = $this->entityManager->getRepository(Publisher::class)->findAll();
        foreach ($publishers as $publisher) {
            $this->entityManager->remove($publisher);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    public function removePublisherById(int $id): void
    {
        $publisher = $this->entityManager->getRepository(Publisher::class)->find($id);
        if(!$publisher instanceof Publisher) {
            throw new EntityNotFoundException($id);
        }
        $this->entityManager->remove($publisher);
        $this->entityManager->flush();

    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function importPublishers(): array
    {
        try {
            $publishers = $this->apiClient->fetchAllPages('producers');
            if($publishers) {
                $this->removeAllPublishersFromDatabase();
                foreach ($publishers as $publisher) {
                    $publisherObject = $this->buildPublisherObject($publisher);
                    $this->entityManager->persist($publisherObject);
                    $this->entityManager->flush();
                }
                return $publishers;
            }

        }
        catch (\Exception $e) {
            throw new \Exception("Publishers import error: ".$e->getMessage());
        }
        return [];
    }

    public function buildPublisherObject($publisher): ?Publisher
    {
        if(is_array($publisher) && !empty($publisher)){
            $publisherObject = new Publisher();
            if(isset($publisher['name'])){
                $publisherObject->setName($publisher['name']);
            }
            if(isset($publisher['producer_id'])){
                $publisherObject->setShoperId($publisher['producer_id']);
            }
            return $publisherObject;
        }
        return null;
    }
}