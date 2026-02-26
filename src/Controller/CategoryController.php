<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



final class CategoryController extends AbstractController
{
    #[Route('/admin/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }
    #[Route('/admin/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManagerInterface, Request $request): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManagerInterface->persist($category);
            $entityManagerInterface->flush();
            
            $this->addFlash('success', 'Votre catégorie à bien été créée');
            
            return $this->redirectToRoute('app_category');
        }
        return $this->render('category/newCategory.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/admin/category/update/{id}', name: 'app_category_update')]
    public function updateCategory(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('info', 'Votre catégorie a bien été modifiée');
            
            return $this->redirectToRoute('app_category');
        }
        return $this->render('category/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route("/admin/category/delete/{id}", name: 'app_category_delete')]
    public function deleteCategory(Category $category, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($category);
        $entityManager->flush();
        $this->addFlash('danger', 'Votre catégorie à bien été supprimée');
        return $this->redirectToRoute("app_category");
    }
    
}