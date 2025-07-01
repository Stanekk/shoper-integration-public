<?php

namespace App\Monolog\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use App\Entity\LogEntry;
use Monolog\LogRecord;

class DatabaseLoggerHandler extends AbstractProcessingHandler
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function write(LogRecord $record): void
    {
        $logEntry = new LogEntry();
        $logEntry->setChannel($record['channel']);
        $logEntry->setLevel($record['level']);
        $logEntry->setLevelName($record['level_name']);
        $logEntry->setMessage($record['message']);
        $logEntry->setContext($record['context']);
        $logEntry->setExtra($record['extra']);
        $logEntry->setCreatedAt(new \DateTimeImmutable());


        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();
    }
}