<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Durand Soline <Solined.independant@php.net>
 * @version Version20210301074205 : video 54
 * fille de Order
 */
class OrderCancelController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande/erreur/{stripeSessionId}", name="order_cancel")
     */
    public function index($stripeSessionId): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);
         
        if ( !$order || $order->getUser() != $this->getUser() ) {
            return $this->redirectToRoute('home');
        }

        // Envoyer un email au client pr lui indiquer l'échec de paiement  

        return $this->render('order_cancel/index.html.twig', [
            'order' => $order
        ]);
    }
}
