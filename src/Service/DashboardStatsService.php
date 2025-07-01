<?php

namespace App\Service;

use App\Api\ShoperConnectionApi;
use App\Entity\ImporterFileData;
use App\Entity\ImportProductsStats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DashboardStatsService
{

    private ShoperConnectionApi $shoperConnectionApi;
    private EntityManagerInterface $entityManager;


    public function __construct(ShoperConnectionApi $shoperConnectionApi, EntityManagerInterface $entityManager)
    {
        $this->shoperConnectionApi = $shoperConnectionApi;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getDashboardStats(): array
    {
        $response = $this->shoperConnectionApi->makeApiRequest('dashboard-stats');
        $general = $this->shoperConnectionApi->getDataFromResponse($response,'general');
        $today = $this->shoperConnectionApi->getDataFromResponse($response,'today');
        $productImportStats = $this->getProductImportStats();
        $fileImportStats = $this->entityManager->getRepository(ImporterFileData::class)->findAll();

        return [
            'general' => $general,
            'today' => $today,
            'product_import_stats' => $productImportStats,
            'fileImportStats' => $fileImportStats
        ];

    }

    public function getProductImportStats(): array
    {
        $stats = [];

        $productImportStats = $this->entityManager->getRepository(ImportProductsStats::class)->findAll();
        if (!empty($productImportStats) && $productImportStats[0] instanceof ImportProductsStats) {
            $importStats = $productImportStats[0];

            $stats = [
                'totalProducts' => $importStats->getTotalProducts(),
                'noEan' => $importStats->getNoEan(),
                'updated' => $importStats->getUpdated(),
                'executionTime' => $importStats->getExecutionTime(),
                'createdAt' => $importStats->getCreatedAt(),
                'new' => $importStats->getNew(),
            ];
        }

        return $stats;
    }

}