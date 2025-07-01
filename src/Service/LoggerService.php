<?php

namespace App\Service;

use App\Repository\LogEntryRepository;
use Psr\Log\LoggerInterface;

class LoggerService
{

    private LoggerInterface $importerLogger;
    private LoggerInterface $userLogger;
    private LoggerInterface $apiLogger;
    private LogEntryRepository $logEntryRepository;


    public function __construct(LoggerInterface $importerLogger,LoggerInterface $userLogger, LoggerInterface $apiLogger, LogEntryRepository $logEntryRepository)
    {
        $this->importerLogger = $importerLogger;
        $this->userLogger = $userLogger;
        $this->apiLogger = $apiLogger;
        $this->logEntryRepository = $logEntryRepository;
    }

    public function getImporterLogger(): LoggerInterface
    {
        return $this->importerLogger;
    }

    public function getUserLogger(): LoggerInterface
    {
        return $this->userLogger;
    }

    public function getApiLogger(): LoggerInterface
    {
        return $this->apiLogger;
    }

    public function getLogs($onlyCustomChannels = false): array
    {
        $findByChannels = [];

        if($onlyCustomChannels) {
            $findByChannels = ['api', 'user', 'importer'];
        }
        if(!empty($findByChannels)) {
            return $this->logEntryRepository->findBy([
                'channel' => $findByChannels,
            ]);
        }

        return $this->logEntryRepository->findAll();

    }
}