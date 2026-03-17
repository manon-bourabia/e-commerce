<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class Cart
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /**
     * @return array{cart: array<int, array{product: \App\Entity\Product, quantity: int, subtotal: int}>, total: int}
     */
    public function getCart(SessionInterface $session): array
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if (null === $product) {
                continue;
            }

            $subtotal = $product->getPrice() * $quantity;
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        return ['cart' => $items, 'total' => $total];
    }
}
