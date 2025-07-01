<?php

namespace App\Controller;

use App\Entity\Wholesaler;
use App\Exception\EntityNotFoundException;
use App\Form\Type\WholesalerType;
use App\Service\LoggerService;
use App\Service\WholesalersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WholesalersController extends AbstractController
{
    #[Route(path: '/wholesalers', name: 'app_wholesalers', methods: ['GET', 'POST'])]
    public function index(Request $request, WholesalersService $wholesalersService, LoggerService $loggerService): Response
    {
        $wholesalers = $wholesalersService->getWholesalers();
        $wholesaler = new Wholesaler();
        $wholesalerFormErrors = [];
        $wholesalerForm = $this->createForm(WholesalerType::class, $wholesaler);
        $wholesalerForm->handleRequest($request);
        $currentUser = $this->getUser();

        if ($wholesalerForm->isSubmitted() && $wholesalerForm->isValid()) {
            try {
                $wholesalersService->saveWholesaler($wholesaler);
                $loggerService->getUserLogger()->info('User: ' . $currentUser->getEmail(). ' created new wholesaler: ' . $wholesaler->getName());
                $this->addFlash('success', 'Wholesalers saved');
                return $this->redirectToRoute('app_wholesalers');
            } catch (\Exception $e) {
                $loggerService->getUserLogger()->error('Wholesales not created, error: ' . $e->getMessage(), [$e]);
                $this->addFlash('error', 'An error occurred while saving the settings.');
            }
        }

        foreach ($wholesalerForm->getErrors(true) as $error) {
            $wholesalerFormErrors[] = $error->getMessage();
        }

        return $this->render('pages/wholesalers/wholesalers.html.twig', [
            'wholesalers' => $wholesalers,
            'wholesalerForm' => $wholesalerForm->createView(),
            'wholesalerFormErrors' => $wholesalerFormErrors,
        ]);
    }

    #[Route(path: '/wholesalers/delete/{id}', name: 'app_wholesalers_delete', methods: ['POST'])]
    public function delete(Request $request, WholesalersService $wholesalersService, LoggerService $loggerService, int $id): Response
    {
        if (!$this->isCsrfTokenValid('delete_single_'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_wholesalers');
        }

        try {
            $wholesalersService->removeWholesalerById($id);
            $currentUser = $this->getUser();
            $loggerService->getUserLogger()->info('User: ' . $currentUser->getEmail(). ' deleted wholesaler: ' . $id);
            $this->addFlash('success', 'Wholesaler deleted');
        } catch (EntityNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while deleting the wholesaler.');
        }
        return $this->redirectToRoute('app_wholesalers');
    }
}