<?php

namespace App\Controller;

use App\Service\DashboardStatsService;
use App\Service\EmailService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{

    #[Route('/', name: 'app_root')]
    public function rootRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route(path: '/home', name: 'app_home', methods: ['GET'])]
    public function index(DashboardStatsService $dashboardStatsService): Response
    {
        $stats = [];
        try {
            $stats = $dashboardStatsService->getDashboardStats();
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}