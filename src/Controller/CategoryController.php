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
    #[Route('/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findAll();
        return $this->render('category/index.html.twig', [
            'category' => $category,
        ]);
    }
    #[Route('/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManagerInterface, Request $request): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManagerInterface->persist($category);
            $entityManagerInterface->flush();
        }
        return $this->render('category/newCategory.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/category/update/{id}', name: 'app_category_update')]
    public function update(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_category');
        }
        return $this->render('category/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
