<?php

namespace App\Controller;

use App\Api\ShoperConnectionApi;
use App\Exception\ApiException;
use App\Form\Type\SearchType;
use App\Form\Type\ImporterType;
use App\Service\AvailabilityService;
use App\Service\ImporterService;
use App\Service\LoggerService;
use App\Service\WholesalersService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImporterController extends AbstractController
{
    #[Route(path: '/importer', name: 'app_importer', methods: ['GET', 'POST'])]
    public function index(Request $request, WholesalersService $wholesalersService, ImporterService $importerService, AvailabilityService $availabilityService, LoggerService $loggerService): Response
    {
        $availabilities = [];
        $wholesalers = $wholesalersService->getWholesalers();

        try {
            $availabilities = $availabilityService->getAvailabilities();
        } catch (ApiException $exception) {
            $loggerService->getImporterLogger()->error('Importer error', [$exception->getMessage(), $exception->getCode(), $exception->getTraceAsString()]);
            $this->addFlash('error', $exception->getMessage());
        }


        $importerForm = $this->createForm(ImporterType::class, null, [
            'wholesalers' => $wholesalers,
            'availabilities' => $availabilities,
        ]);

        $importerForm->handleRequest($request);

        if ($importerForm->isSubmitted() && $importerForm->isValid()) {
            try {
                $importerService->clearImporterDatabase();
                $importerService->clearImportFilesStats();
                foreach ($wholesalers as $wholesaler) {
                    $file = $importerForm->get('wholesaler_file-' . $wholesaler->getId())->getData();
                    if ($file) {
                        $importerService->processFile($file, $wholesaler, $importerForm);
                    }
                }

                return $this->redirectToRoute('app_importer_collected_data');

            } catch (\Throwable $exception) {
                $loggerService->getUserLogger()->error('Importer error', [$exception->getMessage(), $exception->getCode(), $exception->getTraceAsString()]);
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('pages/importer/importer.html.twig', [
            'importerForm' => $importerForm->createView(),
            'wholesalers' => $wholesalers,
        ]);
    }

    #[Route(path: '/importer/collected-data', name: 'app_importer_collected_data', methods: ['GET','POST'])]
    public function collectedData(Request $request, ImporterService $importerService, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');

        $filtersForm = $this->createForm(SearchType::class,  ['filter' => $filter], [
            'method' => 'GET'
        ]);

        $queryBuilder = $importerService->getImportDataQueryBuilder($filter);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('pages/importer/collected-data.html.twig', [
            'pagination' => $pagination,
            'filtersForm' => $filtersForm->createView(),
        ]);
    }

    #[Route(path: '/importer/execute-single/{id}', name: 'app_importer_execute_single', methods: ['GET'])]
    public function executeSingle(ImporterService $importerService, LoggerService $loggerService, int $id): Response
    {
        try {
            $importerService->executeSingleImport($id);
            $this->addFlash('success', 'Product successfully updated');
        } catch (\Throwable $exception) {
            $loggerService->getImporterLogger()->error('Single import error', $exception->getTrace());
            $this->addFlash('error', 'An error occurred while executing the import.');
        }
        return $this->redirectToRoute('app_importer_collected_data');
    }

    #[Route(path: '/importer/delete-single/{id}', name: 'app_importer_delete_single', methods: ['POST'])]
    public function deleteSingle(Request $request, ImporterService $importerService, LoggerService $loggerService, int $id): Response
    {
        if (!$this->isCsrfTokenValid('delete_single_'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_importer_collected_data');
        }

        try {
            $importerService->deleteSingle($id);
            $this->addFlash('success', 'Import data record id: ' . $id . ' has been deleted successfully');
        } catch (\Throwable $exception) {
            $loggerService->getImporterLogger()->error('Delete error ID '.$id, [$exception]);
            $this->addFlash('error', 'Error when deleting a single import data record');
        }

        return $this->redirectToRoute('app_importer_collected_data');
    }
}