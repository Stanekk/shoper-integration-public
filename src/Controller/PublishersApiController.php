<?php

namespace App\Controller;

use App\Mapper\PublisherMapper;
use App\Service\PublishersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublishersApiController extends AbstractController
{

    #[Route(path: '/api/publishers', name: 'app_api_publishers', methods: ['GET'])]
    public function index(PublishersService $publishersService): JsonResponse
    {
        $publishers = $publishersService->getPublishers();

        if (!$publishers) {
            return $this->json(['publishers' => []], Response::HTTP_OK);
        }

        $publishersDTO = [];
        foreach ($publishers as $publisher) {
            $publishersDTO[] = PublisherMapper::toDTO($publisher);
        }

        return $this->json(['publishers' => $publishersDTO], Response::HTTP_OK);
    }
}