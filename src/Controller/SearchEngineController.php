<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchEngineController extends AbstractController
{
    #[Route('/search/engine', name: 'app_search_engine', methods : ['GET','POST'])]
    public function index(ProductRepository $productRepository, Request $request): Response 
    {
        $keyword = $request->query->get('query');
        $products = [];
        if($keyword){
            $products = $productRepository->searchEngine($keyword);
        }
        return $this->render('search_engine/index.html.twig', [
            'products' => '$products',
            'keyword => "$keyword',
        ]);
    }
}
