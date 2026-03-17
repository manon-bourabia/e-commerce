<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository) {} //mon product repo n'a plus besoin d'être rappeler dans mon controller, il est en lecture seule et en privé, il n'est pas modifiable. Sert a injecter des dependances dans notre controller.
    
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $this->productRepository->find($id),
                    'quantity' => $quantity
                ];
            }
        }
        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));

        return $this->render('cart/index.html.twig', [
            'items' => $cartWithData,
            'total' => $total
        ]);
    }

    #[Route("/cart/add/{id}", name: "app_cart_new", methods: ['GET'])]
    public function addProductToCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }
        
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

     #[Route("/cart/remove/{id}", name: "app_cart_product_remove", methods: ['GET'])]
    public function removeProductFromCart($id, SessionInterface $sessionInterface): Response
    {
       $cart = $sessionInterface->get('cart', []);
         if (!empty($cart[$id])) {
            unset ($cart[$id]);
         }

         $sessionInterface->set('cart', $cart);
        return $this->redirectToRoute('app_cart');
    }

    #[Route("/cart/remove", name: "app_cart_remove", methods: ['GET'])]
    public function remove(SessionInterface $sessionInterface): Response
    {
        $sessionInterface->set('cart', []);

        return $this->redirectToRoute('app_cart');
    }
}