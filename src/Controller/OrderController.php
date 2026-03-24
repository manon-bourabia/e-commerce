<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\Cart;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use id;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, EntityManagerInterface $em, SessionInterface $session, ProductRepository $productRepository, Cart $cart): Response
    {
        $data = $cart->getCart($session);

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($order->isPayOnDelivery()) {

                if(!empty($data['total'])){
                    $order->setTotalPrice($data['total']);
                    $order->setCreatedAt(new DateTimeImmutable());

                    $em->persist($order);
                    $em->flush();
                    
                    foreach($data['cart'] as $value) {
                        $orderProduct = new OrderProducts();
                        $orderProduct->setOrder($order);
                        $orderProduct->setProduct($value['product']);
                        $orderProduct->setQuantity($value['quantity']);
                        $em->persist($orderProduct);
                        $em->flush();
                    }
                }

                $session->set('cart', []);
                return $this->redirectToRoute('app_order_message');
            }
        }
        
        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total']
        ]);
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();

        return new Response(json_encode(['status'=>200, 'message'=>'on', 'content'=>$cityShippingPrice]));
    }

    #[Route('/order/message', name:'app_order_message')]
    public function orderMessage():Response
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/editor/order', name:'app_orders_show')]
    public function getAllOrder(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request):Response
    {
        $data = $orderRepository->findby([], ['id'=>'DESC']);
        $orders = $orderRepository->findAll();
        $orders = $paginator->paginate(
            $data,
        $request->query->getInt('page', 1),
        3
        );

        return $this->render('order/order_list.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate (OrderRepository $orderRepository, EntityManagerInterface $entityManager, $id) : Response
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addFlash('success', "Modification réuss");
        return $this->redirectToRoute('app_orders_shows');
    }

    #[Route('editor/order/{id}/remove', name : 'app_order_remove')]
    public function removeOrder (Order $order, EntityManagerInterface $entityManager):Response
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimé');
        return $this->redirectToRoute('app_orders_show');
    }
}
