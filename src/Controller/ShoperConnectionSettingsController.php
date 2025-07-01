<?php

namespace App\Controller;

use App\Api\ShoperConnectionApi;
use App\Entity\ShoperConnectionSettings;
use App\Form\Type\ShoperConnectionSettingType;
use App\Service\LoggerService;
use App\Service\ShoperConnectionSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ShoperConnectionSettingsController extends AbstractController
{
    #[Route(path: '/shoper-connection', name: 'app_shoper_connection')]
    public function index(Request $request,ShoperConnectionSettingsService $shoperConnectionSettingsService, LoggerService $loggerService, ShoperConnectionApi $shoperConnectionApi)
    {
        $settings = $shoperConnectionSettingsService->getShoperConnectionSettings();
        $formErrors = [];

        if(!$settings instanceof ShoperConnectionSettings){
            $settings = new ShoperConnectionSettings();
        }

        if($settings->getCreatedAt() instanceof \DateTimeImmutable){
            $createdAt = $settings->getCreatedAt()->format('Y-m-d H:i:s');
        } else {
            $createdAt = null;
        }

        $form = $this->createForm(ShoperConnectionSettingType::class, $settings);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            try {
                $shoperConnectionSettingsService->saveShoperConnectionSettings($settings);
                $shoperConnectionApi->deleteToken();
                $loggerService->getUserLogger()->info('Shoper Connection settings saved');
                $this->addFlash('success', 'Shoper connection settings saved');
                return $this->redirectToRoute('app_shoper_connection');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving the settings.');
            }
        }

        foreach ($form->getErrors(true) as $error) {
            $formErrors[] = $error->getMessage();
        }

        return $this->render('pages/shoper_connection/shoper-connection.html.twig', [
            'form' => $form->createView(),
            'formErrors' => $formErrors,
            'createdAt' => $createdAt
        ]);
    }
}