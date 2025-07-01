<?php

namespace App\Command;

use App\Service\LoggerService;
use App\Service\ProductsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from Shoper Store',
    aliases: ['app:import-products'],
    hidden: false
)]
class ImportProductsCommand extends Command
{
    private $productsService;
    private $loggerService;

    public function __construct(ProductsService $productsService, LoggerService $loggerService)
    {
        $this->productsService = $productsService;
        $this->loggerService = $loggerService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Importing products...');

        try {
            $this->productsService->importProducts();

            $output->writeln('<info>Products imported successfully.</info>');
        } catch (\Exception $e) {
            $this->loggerService->getImporterLogger()->error($e->getMessage());
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
