<?php

namespace App\Controller;

use App\Service\LoggerService;
use App\Service\PublishersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublishersController extends AbstractController
{
    #[Route(path: '/publishers', name: 'app_publishers', methods: ['GET'])]
    public function index(PublishersService $publishersService): Response
    {
        $publishers = $publishersService->getPublishers();

        return $this->render('pages/publishers/publishers.html.twig',[
            'publishers' => $publishers
        ]);
    }

    #[Route(path: '/publishers/import', name: 'app_publishers_import', methods: ['POST'])]
    public function importAction(PublishersService $publishersService, LoggerService $loggerService, Request $request): Response
    {

        if (!$this->isCsrfTokenValid('import_publishers', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_publishers');
        }

        try {
            $publishers = $publishersService->importPublishers();
            $this->addFlash('success', 'A list of publishers was imported, found: '.count($publishers));
        } catch (\Throwable $exception) {
            $loggerService->getUserLogger()->error('Import publishers error: ' . $exception->getMessage(), [$exception->getTrace()]);
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('app_publishers');
    }

    #[Route(path: '/publishers/remove/{id}', name: 'app_publishers_delete_single', methods: ['POST'])]
    public function deleteSingleAction(Request $request, PublishersService $publishersService, LoggerService $loggerService , $id): Response
    {

        if (!$this->isCsrfTokenValid('delete_single_'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_publishers');
        }

        try {
            $publishersService->removePublisherById($id);
            $this->addFlash('success', 'Publisher was successfully deleted');
        }
        catch (\Exception $exception) {
            $loggerService->getUserLogger()->error('Delete publisher error: ' . $exception->getMessage());
            $this->addFlash('error', 'An unexpected error occurred.');
        }
        return $this->redirectToRoute('app_publishers');
    }

    #[Route(path: '/publishers/delete-all', name: 'app_publishers_delete-all', methods: ['POST'])]
    public function deleteAllAction(Request $request,PublishersService $publishersService, LoggerService $loggerService): Response
    {
        if (!$this->isCsrfTokenValid('delete_all_publishers', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_publishers');
        }

        try {
            $publishersService->removeAllPublishersFromDatabase();
            $this->addFlash('success', 'All publishers have been removed');
        } catch (\Exception $exception) {
            $loggerService->getUserLogger()->error('Delete publishers error: ' . $exception->getMessage());
            $this->addFlash('error', 'An unexpected error occurred.');
        }
        return $this->redirectToRoute('app_publishers');
    }
}