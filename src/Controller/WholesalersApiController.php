<?php

namespace App\Controller;

use App\Exception\ApiException;
use App\Exception\EntityNotFoundException;
use App\Mapper\WholesalerMapper;
use App\Service\WholesalersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WholesalersApiController extends AbstractController
{
    #[Route(path: '/api/wholesalers/{id?}', name: 'app_api_get_wholesalers', methods: ['GET'])]
    public function index($id, WholesalersService $wholesalersService): JsonResponse
    {
        if ($id === null) {
            $wholesalers = $wholesalersService->getWholesalers();
            $wholesalersArray = [];
            foreach ($wholesalers as $wholesaler) {
                $wholesalersArray[] = WholesalerMapper::toDTO($wholesaler);
            }
            return $this->json(
                $wholesalersArray,
                Response::HTTP_OK,
            );
        }

        $wholesaler = $wholesalersService->getWholesaler($id);

        if (!$wholesaler) {
            return $this->json([], Response::HTTP_OK);
        }

        $wholesalerDTO = WholesalerMapper::toDTO($wholesaler);

        return $this->json(
            $wholesalerDTO,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/api/wholesalers/{id}/assign', name: 'app_api_wholesaler_assign', methods: ['POST'])]
    public function assign(Request $request, $id, WholesalersService $wholesalersService): JsonResponse
    {
        try {
            $requestData = json_decode($request->getContent(), true);
            if(!isset($requestData['publishersIds'])) {
                return $this->json([], Response::HTTP_BAD_REQUEST);
            }
            $publishersIds = $requestData['publishersIds'];
            $wholesaler = $wholesalersService->getWholesaler($id);
            $wholesalersService->assignPublishersToWholesaler($wholesaler,$publishersIds);
        }
        catch (EntityNotFoundException|ApiException $exception) {
            return new JsonResponse($exception->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return new JsonResponse('Server error',Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null,Response::HTTP_OK);
    }
}