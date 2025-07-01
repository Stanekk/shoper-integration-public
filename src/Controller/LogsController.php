<?php

namespace App\Controller;

use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogsController extends AbstractController
{
    #[Route(path: '/logs', name: 'app_logs', methods: ['GET'])]
    public function index(Request $request, LoggerService $loggerService): Response
    {
        $logs = $loggerService->getLogs(true);

        return $this->render('pages/logs/logs.html.twig',[
            'logs' => $logs
        ]);
    }

}