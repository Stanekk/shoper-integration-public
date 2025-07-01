<?php

namespace App\Service;

use App\Helpers\StringSanitizer;
use App\Api\ShoperConnectionApi;
use App\Entity\ImporterData;
use App\Entity\ImporterFileData;
use App\Entity\Product;
use App\Entity\Wholesaler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Throwable;

class ImporterService
{
    private ShoperConnectionApi $shoperApi;
    private EntityManagerInterface $entityManager;
    private ProductsService $productsService;
    private LoggerService $loggerService;
    private CsvParserService $csvParserService;

    public function __construct(
        ShoperConnectionApi $shoperApi,
        EntityManagerInterface $entityManager,
        ProductsService $productsService,
        LoggerService $loggerService,
        CsvParserService $csvParserService
    ) {
        $this->shoperApi = $shoperApi;
        $this->entityManager = $entityManager;
        $this->productsService = $productsService;
        $this->loggerService = $loggerService;
        $this->csvParserService = $csvParserService;
    }

    /**
     * @throws Throwable
     */
    public function processFile(UploadedFile $file, Wholesaler $wholesaler, $importerForm): void
    {
        $data = $this->csvParserService->parseFile($file, $wholesaler);
        $this->saveDataToDatabase($data, $importerForm);
    }

    public function saveDataToDatabase($data, $importerForm)
    {
        try {
            if ($data) {
                $stats = [
                    'fileName' => null,
                    'productsFound' => 0,
                ];

                $skippedStats = [
                    'notFound' => 0,
                    'statusExcluded' => 0,
                    'stockUnchanged' => 0
                ];

                $statuses = $this->getStatusesFromForm($importerForm);
                $products = $this->productsService->getProducts();

                $eanMap = [];

                foreach ($products as $product) {
                    $eanMap[$product->getEan()] = $product;
                }

                foreach ($data as $row) {
                    $ean = $row['EAN'];
                    if (!isset($eanMap[$ean])) {
                        $skippedStats['notFound']++;
                        continue;
                    }

                    $product = $eanMap[$ean];

                    if ($this->excludeProductByStatuses($statuses, $product)) {
                        $skippedStats['statusExcluded']++;
                        continue;
                    }

                    if (!$this->stockHasChanged($row['InStock'], $product->getStock())) {
                        $skippedStats['stockUnchanged']++;
                        continue;
                    }

                    $importerData = new ImporterData();
                    $importerData->setShoperProductId($product->getShoperProductId());
                    $importerData->setEan($product->getEan());
                    $importerData->setProductName($product->getName());
                    $importerData->setPermalink($product->getPermalink());
                    $importerData->setCategory(StringSanitizer::sanitize($row['Category']));
                    $importerData->setFilePublisher(StringSanitizer::sanitize($row['FilePublisher']));
                    $importerData->setStock($row['InStock']);
                    $importerData->setOldStock($product->getStock());
                    $importerData->setFromFile(StringSanitizer::sanitize($row['Filename']));
                    $this->entityManager->persist($importerData);
                    $stats['productsFound']++;
                    $stats['fileName'] = StringSanitizer::sanitize($row['Filename']);
                }
                $this->entityManager->flush();
                $stats['skippedStats'] = $skippedStats;
                $this->saveImporterFileStats($stats);
            }
        } catch (\Exception $exception) {
            $this->loggerService->getImporterLogger()->error('Import error: ' . $exception->getMessage(), [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
            ]);
            return $exception;
        }
    }

    public function excludeProductByStatuses($statuses, Product $product): bool
    {
        if (empty($statuses)) {
            return false;
        }

        if ($product->getStatus()) {
            if (in_array($product->getStatus(), $statuses)) {
                return true;
            }
        }

        return false;
    }

    public function getStatusesFromForm($importerForm): array
    {
        $data = $importerForm->getData();
        if (isset($data['exclude_product_status'])) {
            return $data['exclude_product_status'];
        }
        return [];
    }

    public function stockHasChanged($stockFromFile, $stockFromStore): bool
    {
        if (!isset($stockFromFile) || !isset($stockFromStore)) {
            return false;
        }

        if (intval($stockFromStore) !== intval($stockFromFile)) {
            return true;
        }
        return false;
    }

    public function clearImporterDatabase(): void
    {
        $importerData = $this->entityManager->getRepository(ImporterData::class)->findAll();
        if ($importerData) {
            foreach ($importerData as $data) {
                $this->entityManager->remove($data);
            }
        }
        $this->entityManager->flush();
    }

    public function getImportDataQueryBuilder(?string $filter = null): QueryBuilder
    {
        $importerDataRepository = $this->entityManager->getRepository(ImporterData::class);
        $qb = $importerDataRepository->createQueryBuilder('p');

        if ($filter) {
            $terms = array_filter(array_map('trim', preg_split('/[,]+/', $filter)));

            $orX = $qb->expr()->orX();

            foreach ($terms as $i => $term) {
                $param = 'term_' . $i;

                $orX->add($qb->expr()->orX(
                    $qb->expr()->like('p.ean', ':' . $param),
                    $qb->expr()->like('p.shoperProductId', ':' . $param),
                    $qb->expr()->like('p.category', ':' . $param),
                    $qb->expr()->like('p.filePublisher', ':' . $param),
                    $qb->expr()->like('p.productName', ':' . $param),
                    $qb->expr()->like('p.permalink', ':' . $param),
                    $qb->expr()->like('p.fromFile', ':' . $param)
                ));

                $qb->setParameter($param, '%' . $term . '%');
            }

            $qb->andWhere($orX);
        }

        return $qb;
    }

    public function getSingleImportById($id)
    {
        return $this->entityManager->getRepository(ImporterData::class)->findOneBy(['id' => $id]);
    }

    /**
     * @throws \Throwable
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function executeSingleImport($id): void
    {
        $importerDataSingle = $this->getSingleImportById($id);
        if (!$importerDataSingle instanceof ImporterData) {
            $this->loggerService->getImporterLogger()->alert('Single import with id:' . $id . ' not found');
            return;
        }

        $productId = $importerDataSingle->getShoperProductId();

        $dataToUpdate = [
            'stock' => [
                'stock' => $importerDataSingle->getStock(),
            ]
        ];

        $this->productsService->updateShoperProduct($productId, $dataToUpdate, $importerDataSingle);
    }

    /**
     * @throws \Throwable
     */
    public function deleteSingle($id): void
    {
        $importerDataSingle = $this->getSingleImportById($id);

        if (!$importerDataSingle instanceof ImporterData) {
            $this->loggerService->getImporterLogger()->alert('Single import with id:' . $id . ' not found');
            return;
        }

        try {
            $this->entityManager->remove($importerDataSingle);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->loggerService->getImporterLogger()->alert('Single import with id:' . $id . ' not found');
            throw $exception;
        }
    }

    public function saveImporterFileStats(array $stats): void
    {
        $importFileData = new ImporterFileData();
        $importFileData->setFileName($stats['fileName']);
        $importFileData->setNumberOfProducts($stats['productsFound']);
        $importFileData->setNumberOfNotFoundProducts($stats['skippedStats']['notFound']);
        $importFileData->setNumberOfProductsExcludedByStatus($stats['skippedStats']['statusExcluded']);
        $importFileData->setNumberOfProductsStockNotChanged($stats['skippedStats']['stockUnchanged']);

        $importFileData->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($importFileData);
        $this->entityManager->flush();
    }

    public function clearImportFilesStats(): void
    {
        $filesStats = $this->entityManager->getRepository(ImporterFileData::class)->findAll();

        if ($filesStats) {
            foreach ($filesStats as $fileStat) {
                $this->entityManager->remove($fileStat);
            }
            $this->entityManager->flush();
        }
    }

}