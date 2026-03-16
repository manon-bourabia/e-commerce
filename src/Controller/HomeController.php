<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $products = $productRepository->findAll(); 
        
        return $this->render('home/index.html.twig', [
            'products'=>$products,
            'categories'=>$categoryRepository->findAll()
        ]);
    }
    #[Route('/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function showProduct(Product $product, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $lastProductsAdd = $productRepository->findBy([], ['id'=>'DESC'],3);
        
        return $this->render('home/show.html.twig', [
            'product'=>$product,
            'categories'=>$categoryRepository->findAll(),
            'products'=>$lastProductsAdd
        ]);
    }
    #[Route('/product/subCategory/{id}/filter', name: 'app_home_product_filter')]
    public function filter ($id, SubCategoryRepository $subCategoryRepository) : Response
    {
        $product = $subCategoryRepository->find($id)->getProduct();
        $subCategory = $subCategoryRepository->find($id);
        
        return $this->render('home/filter.html.twig',[
            'products'=>$product,
            'subCategory'=>$subCategory
        ]);
    }
}