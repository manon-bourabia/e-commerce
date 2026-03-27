<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Service\Cart;
use App\Service\StripePayment;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) 
    {
    }

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request, 
        SessionInterface $session, 
        EntityManagerInterface $entityManager,
        Cart $cartService
    ): Response {
        
        $data = $cartService->getCart($session);
        
        if (empty($data['cart'])) {
            return $this->redirectToRoute('app_home');
        }

        // --- DEBUT DE LA MODIFICATION : PRE-REMPLISSAGE ---
        $user = $this->getUser(); // On récupère l'utilisateur connecté
        $order = new Order();

        if ($user) {
            // On remplit l'objet Order avec les données de l'User
            // Vérifie bien que ces méthodes existent dans ton entité User
            $order->setFirstName($user->getFirstName());
            $order->setLastName($user->getLastName());
            $order->setEmail($user->getEmail());
            
            // Si tu as un champ adresse ou téléphone dans ton User, décommente ci-dessous :
            // $order->setAdresse($user->getAdresse());
            // $order->setPhone($user->getPhone());
        }
        // --- FIN DE LA MODIFICATION ---

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $shippingCost = $order->getCity()->getShippingCost();
            $totalFinal = $data['total'] + $shippingCost;

            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setTotalPrice($totalFinal);
            $order->setIsPaymentCompleted(false);

            // On lie la commande à l'utilisateur en base de données s'il est connecté
            if ($user) {
                $order->setUser($user); 
            }

            $entityManager->persist($order);

            foreach ($data['cart'] as $item) {
                $orderProduct = new OrderProducts();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($item['product']);
                $orderProduct->setQuantity($item['quantity']);
                $entityManager->persist($orderProduct);
            }
            
            $entityManager->flush();

            if ($order->isPayOnDelivery()) {
                $session->remove('cart');

                $html = $this->renderView('mail/orderConfirm.html.twig', ['order' => $order]);
                $email = (new Email())
                    ->from("manon.elm@hotmail.com")
                    ->to($order->getEmail())
                    ->subject('Confirmation de votre commande')
                    ->html($html);
                
                $this->mailer->send($email);

                $this->addFlash('success', 'Commande validée (Paiement à la livraison)');
                return $this->redirectToRoute('app_order_message');
            } else {
                $paymentStripe = new StripePayment();
                $paymentStripe->startPayment($data, $shippingCost, $order->getId());
                
                return $this->redirect($paymentStripe->getStripeRedirectUrl());
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
            'cart' => $data['cart']
        ]);
    }

    #[Route('/order_message', name: 'app_order_message')]
    public function orderMessage(): Response
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        return new Response(json_encode([
            'status' => 200, 
            'content' => $city->getShippingCost()
        ]));
    }
}