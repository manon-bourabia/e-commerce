<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {

        $users = $userRepository -> findAll();
        return $this->render('user/index.html.twig', [
            // 'controller_name' => 'UserController',
            'users' => $users
        ]);
    }
    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor')]
    public function changeRoleToEditor(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', 'Le rôle de l\'utilisateur a été changé avec succès.');
        return $this->redirectToRoute('app_user');
    }
    #[Route('/admin/user/{id}/change-role/{role}', name: 'app_user_change_role')]
    public function changeRole(EntityManagerInterface $entityManager, User $user, $role): Response
    {
        $validRoles = ['ROLE_EDITOR', 'ROLE_USER'];

        if (!in_array($role, $validRoles, true)) {
            $this->addFlash('error', "Le rôle demandé n'est pas valide.");
            return $this->redirectToRoute('app_user');
        }
        
        if ($role !== 'ROLE_USER') {
            $user->setRoles([$role, 'ROLE_USER']);
        } else {
            $user->setRoles([$role]);
        }
        $entityManager->flush();
        
        $this->addFlash('success', "Le rôle $role a bien été attribué à l'utilisateur.");
        
        return $this->redirectToRoute('app_user');
    }
     #[Route('/admin/user/{id}/remove/editor/role ', name: 'app_user_remove_editor_role')]
    public function removeRoleEditor(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('danger', "Le rôle éditeur à bien été retiré à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }
     #[Route('/admin/user/{id}/remove/', name: 'app_user_remove')]
    public function ruserRemove(EntityManagerInterface $entityManager,$id,  UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', "L'utilisateur à bien été supprimé.");
        
        return $this->redirectToRoute('app_user');
    }
}