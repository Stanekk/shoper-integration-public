<?php

namespace App\Service;

use App\Api\ShoperConnectionApi;
use App\Entity\ImporterData;
use App\Entity\ImportProductsStats;
use App\Entity\Product;
use App\Exception\ServiceException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProductsService
{
    private $apiClient;
    private $entityManager;
    private $logger;
    private $availabilityService;

    public function __construct(ShoperConnectionApi $apiClient, EntityManagerInterface $entityManager, LoggerService $logger, AvailabilityService $availabilityService)
    {
        $this->apiClient = $apiClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->availabilityService = $availabilityService;

    }

    public function importProducts(): array
    {
        $errors = [];
        $stats = [
            'total_products' => 0,
            'no_ean' => 0,
            'updated' => 0,
            'new' => 0,
            'execution_time' => 0,
        ];

        $startTime = microtime(true);

        try {
            $products = $this->apiClient->fetchAllPages('products');

            if (empty($products)) {
                $errors[] = "No products found";
                return ['errors' => $errors, 'stats' => $stats, 'execution_time' => 0];
            }

            foreach ($products as $productData) {
                $stats['total_products']++;

                if (empty($productData['ean'])) {
                    $stats['no_ean']++;
                }

                $existingProduct = $this->entityManager
                    ->getRepository(Product::class)
                    ->findOneBy(['shoperProductId' => $productData['product_id']]);

                if ($existingProduct) {
                    $this->updateProductFromShoper($existingProduct, $productData);
                    $stats['updated']++;
                } else {
                    $product = $this->createProductFromShoper($productData);
                    $this->entityManager->persist($product);
                    $stats['new']++;
                }
            }

            $this->entityManager->flush();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            $this->logger->getImporterLogger()->alert('Import products error: ' . $e->getMessage());
        }

        $executionTime = round(microtime(true) - $startTime, 2);
        $stats['execution_time'] = $executionTime;

        $this->saveImportStats($stats);

        return $errors;
    }


    public function createProductFromShoper(array $shoperProduct): Product
    {
        if (!isset($shoperProduct['ean'], $shoperProduct['product_id'])) {
            throw new ServiceException('Missing product data');
        }

        $product = new Product();
        $product->setShoperProductId($shoperProduct['product_id']);
        $product->setEan($shoperProduct['ean']);

        $this->mapProductDataFromShoper($product, $shoperProduct);

        return $product;
    }

    public function updateProductFromShoper(Product $product, array $shoperProduct): void
    {
        $this->mapProductDataFromShoper($product, $shoperProduct);
    }

    private function mapProductDataFromShoper(Product $product, array $shoperProduct): void
    {
        $productStatus = $this->getProductStatusFromShoper($shoperProduct);

        $product->setStock($shoperProduct['stock']['stock'] ?? 0);
        $product->setPermalink($shoperProduct['translations']['pl_PL']['permalink'] ?? '');
        $product->setName($shoperProduct['translations']['pl_PL']['name'] ?? '');

        if (isset($productStatus['id'])) {
            $product->setStatus($productStatus['id']);
        }

        if (isset($productStatus['name'])) {
            $product->setStatusDescription($productStatus['name']);
        }
    }

    public function saveImportStats(array $stats): void
    {
        $statsDatabase = $this->entityManager->getRepository(ImportProductsStats::class)->findAll();
        if($statsDatabase) {
            foreach ($statsDatabase as $stat) {
                $this->entityManager->remove($stat);
            }
        }

        $importProductStats = new ImportProductsStats();
        $importProductStats->setTotalProducts($stats['total_products']);
        $importProductStats->setNoEan($stats['no_ean']);
        $importProductStats->setUpdated($stats['updated']);
        $importProductStats->setNew($stats['new']);
        $importProductStats->setExecutionTime($stats['execution_time']);
        $importProductStats->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($importProductStats);
        $this->entityManager->flush();
    }

    public function getProducts($noEan = false): array
    {
        $productRepository = $this->entityManager->getRepository(Product::class);
        if ($noEan) {
            $products = $productRepository->findBy(['ean' => ""]);
        } else {
            $products = $productRepository->findAll();
        }
        return $products;
    }

    public function getProductsDataQueryBuilder($filter): QueryBuilder
    {
        $productRepository = $this->entityManager->getRepository(Product::class);
        $qb = $productRepository->createQueryBuilder('p');

        if ($filter) {
            $terms = array_filter(array_map('trim', preg_split('/[,]+/', $filter)));

            $orX = $qb->expr()->orX();

            foreach ($terms as $i => $term) {
                $paramText = 'term_' . $i;
                $paramInt = 'term_int_' . $i;

                $orX->add($qb->expr()->orX(
                    $qb->expr()->eq('p.id', ':' . $paramInt),
                    $qb->expr()->like('p.ean', ':' . $paramText),
                    $qb->expr()->eq('p.stock', ':' . $paramInt),
                    $qb->expr()->like('p.name', ':' . $paramText),
                    $qb->expr()->eq('p.shoperProductId', ':' . $paramInt),
                    $qb->expr()->eq('p.status', ':' . $paramInt),
                    $qb->expr()->like('p.statusDescription', ':' . $paramText)
                ));

                $qb->setParameter($paramText, '%' . $term . '%');

                if (is_numeric($term)) {
                    $qb->setParameter($paramInt, (int) $term);
                } else {
                    $qb->setParameter($paramInt, -1);
                }
            }


            $qb->andWhere($orX);
        }

        return $qb;
    }

    public function searchProductByEan(string $ean): ?Product
    {
        $productRepository = $this->entityManager->getRepository(Product::class);
        return $productRepository->findOneBy(['ean' => $ean]);
    }

    public function deleteAllProducts(): void
    {
        $products = $this->getProducts();
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }
        $this->entityManager->flush();
    }

    public function getProductStatusFromShoper(array $shoperProductData): array
    {
        $statusId = $shoperProductData['stock']['availability_id']
            ?? $shoperProductData['stock']['calculated_availability_id']
            ?? null;

        if ($statusId === null) {
            return [];
        }

        return $this->availabilityService->getAvailabilityById((int) $statusId);
    }

    /**
     * @throws \Throwable
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateShoperProduct($productId, $updatedData, ImporterData $importerDataSingle): void
    {
        try {
            $oldStock = $importerDataSingle->getOldStock();

            $importerDataSingle->setExecuted(true);
            $this->entityManager->persist($importerDataSingle);
            $this->entityManager->flush();

            $this->apiClient->makeApiRequest('products/' . $productId, 'PUT', [], $updatedData);
            $this->logger->getImporterLogger()->info('Update product success PRODUCT ID: ' . $productId . ' new stock: ' . $updatedData['stock']['stock'] . ' old stock: ' . $oldStock);
        } catch (\Throwable $e) {
            $this->logger->getImporterLogger()->error('Update product error: ' . $e->getMessage(), $e->getTrace());
            throw $e;
        }
    }
}