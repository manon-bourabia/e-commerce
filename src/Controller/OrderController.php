<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Service\StripePayment;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) {}


    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Cart $cart,
        MailerInterface $mailer // Assure-toi que le MailerInterface est bien injecté ici ou en haut
    ): Response {

        // 1. Récupération du panier
        $data = $cart->getCart($session);
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        // 2. Si le formulaire est validé
        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($data['total'])) {

                // Configuration de la commande de base
                $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                $order->setTotalPrice($totalPrice);
                $order->setCreatedAt(new \DateTimeImmutable());
                $order->setIsPaymentCompleted(0);

                $entityManager->persist($order);
                $entityManager->flush();

                // Enregistrement des produits de la commande
                foreach ($data['cart'] as $value) {
                    $orderProduct = new OrderProducts();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($value['product']);
                    $orderProduct->setQuantity($value['quantity']);
                    $entityManager->persist($orderProduct);
                }
                $entityManager->flush();

                // --- DEBUT LOGIQUE EMAIL (Commun à tous) ---
                // On prépare le contenu HTML à partir de ton template Twig
                $html = $this->renderView('mail/orderConfirm.html.twig', [
                    'order' => $order,
                    'cart' => $data['cart']
                ]);

                $email = (new Email())
                    ->from("contact-boutique@gmail.com")
                    ->to($order->getEmail()) // L'adresse que l'utilisateur a tapée
                    ->subject('Confirmation de réception de commande')
                    ->html($html);

                // On envoie le mail à Mailtrap
                $mailer->send($email);
                // --- FIN LOGIQUE EMAIL ---

                // 3. Redirection selon le mode de paiement choisi
                if ($order->isPayOnDelivery()) {
                    // Si paiement à la livraison
                    $session->set('cart', []); // On vide le panier
                    return $this->redirectToRoute('app_order_message');
                } else {
                    // Si paiement par Stripe
                    $paymentStripe = new StripePayment();
                    $shippingCost = $order->getCity()->getShippingCost();
                    $paymentStripe->startPayment($data, $shippingCost, $order->getId());

                    $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();
                    return $this->redirect($stripeRedirectUrl);
                }
            }
        }

        // Affichage du formulaire si pas encore soumis
        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
            'cart' => $data['cart'],
        ]);
    }

    #[Route('/editor/order/{type}/', name: 'app_orders_show')]
    public function getAllOrder($type, OrderRepository $orderRepository, Request $request, PaginatorInterface $paginator): Response
    {
        if ($type == 'is-completed') {
            $data = $orderRepository->findBy(['isCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'pay-on-stripe-not-delivered') {
            $data = $orderRepository->findBy(['isCompleted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'pay-on-stripe-is-delivered') {
            $data = $orderRepository->findBy(['isCompleted' => 1, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'no_delivery') {
            $data = $orderRepository->findBy(['isCompleted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 0], ['id' => 'DESC']);
        }
        //dd($orders);

        $orders = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), //met en place la pagination
            6 //je choisi la limite de 6 commandes par page
        );

        return $this->render('order/order_list.html.twig', [
            "orders" => $orders
        ]);
    }

    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(Request $request, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        // On marque la commande comme livrée/terminée
        $order->setIsCompleted(true);

        // ON AJOUTE CETTE LIGNE : 
        // On considère que si l'admin valide la commande, le paiement est encaissé
        $order->setIsPaymentCompleted(true);

        $entityManager->flush();

        $this->addFlash('success', 'La commande a été marquée comme livrée et payée !');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show', ['type']);
    }

    #[Route('/order_message', name: 'app_order_message')]
    public function orderMessage(): Response
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();

        return new Response(json_encode(['status' => 200, "message" => 'on', 'content' => $cityShippingPrice]));
    }
}
