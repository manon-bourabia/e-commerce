<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $city = $order->getCity();
            $shippingCost = $city->getShippingCost();

            $order->setCreateAt(new \DateTimeImmutable());
            $order->setTotal($total + $shippingCost);

            $entityManager->persist($order);

            foreach ($cartWithData as $item) {
                $orderProduct = new OrderProducts();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($item['product']);
                $orderProduct->setQuantity($item['quantity']);

                $entityManager->persist($orderProduct);
            }
            $entityManager->flush();

            $session->remove('cart');

            $this->addFlash('success', 'Votre commande a été enregistrée !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $total,
            'items' => $cartWithData
        ]);
    }
}
