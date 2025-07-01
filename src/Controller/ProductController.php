<?php

namespace App\Controller;

use App\Form\Type\SearchType;
use App\Service\ProductsService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route(path: '/products', name: 'app_products', methods: ['GET', 'POST'])]
    public function indexAction(Request $request, ProductsService $productsService, PaginatorInterface $paginator): Response
    {

        $filter = $request->query->get('filter');

        $filtersForm = $this->createForm(SearchType::class,  ['filter' => $filter], [
            'method' => 'GET'
        ]);

        $queryBuilder = $productsService->getProductsDataQueryBuilder($filter);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('pages/products/products.html.twig',[
            'pagination' => $pagination,
            'filtersForm' => $filtersForm->createView(),
        ]);
    }

    #[Route(path: '/import-products', name: 'app_products_import', methods: ['POST'])]
    public function importAction(ProductsService $productsService, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('app_products_import_token', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_products');
        }

        $errors = $productsService->importProducts();
        if(count($errors) > 0){
            $this->addFlash("error", 'Import products failed');
        }

        return $this->redirectToRoute('app_products');
    }

    #[Route(path: '/delete-products', name: 'app_products_delete', methods: ['POST'])]
    public function deleteAllAction(ProductsService $productsService, Request $request): Response
    {

        if (!$this->isCsrfTokenValid('app_products_delete_token', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_products');
        }

        $productsService->deleteAllProducts();
        $this->addFlash("success", 'All products have been deleted successfully');

        return $this->redirectToRoute('app_products');
    }

}