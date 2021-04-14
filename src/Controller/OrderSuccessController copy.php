<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Classe\Mail;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Durand Soline <Solined.independant@php.net>
 * @version Version20210301074205 : video 54
 * fille de Order
 * lié à StripeController, OrderController
 */
class OrderSuccessController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande/merci/{stripeSessionId}", name="order_success")
     * 
     * orderRepository recupere la classe/objet order ; appellée par entitymanager->getRepository
     * @todo exception
     */
    public function index($stripeSessionId, Cart $cart): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);
        
        if ( !$order || $order->getUser() != $this->getUser() ) {
            return $this->redirectToRoute('home');
        }

        // Sécurisé le paiement isPaid à false
        /* test de sécu; AF avec passage d'info ds l'url 
            dump($order->getIsPaid());
            $t = 1; */ 
        if ( !$order->getIsPaid() ) {  //(!$t){
            // Vider la session "cart"
            $cart->remove();

            //Modifier le statut isPaid de notre commande à 1     //10:10
            $order->setIsPaid(1);
            $this->entityManager->flush();

            // Envoyer un email au client pr lui confirmer sa commande (=facture)
            $this->sendEmailSuccess($order);

        } /* else {     //marche pas si j'actualise la page success (apres le paiement reussi)
            //exception todo

            //on retourne à la page d'erreur
            return $this->render('order_cancel//index.html.twig', [
                'order' =>$order
            ]);
        } */
       
        //dd($order); //on l'affiche ds le template

        // Afficher les qq infos de la commande de l'utilisateur
        return $this->render('order_success/index.html.twig', [
            'order' =>$order
        ]);
    }

     /**
     * envoi l'email de notification success
     * 
     * cf.OrderCrudController sendEmailOfStatusChange()
     */
    public function sendEmailSuccess(Order $order) {
        $mail    = new Mail();

        $content = "Bonjour ".$order->getUser()->getFirstname().",<br/><br/>Merci pour votre commande sur la boutique dédiée au Made in France.<br/><br/>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus convallis sapien nec velit volutpat aliquam. Nulla at sapien dui. Integer tincidunt pulvinar mi ut placerat. Duis at massa vel velit tristique iaculis at at neque. Fusce lobortis, mi sit amet placerat tristique, quam justo ornare elit, et bibendum turpis metus quis nisi. Pellentesque interdum lectus sed quam semper tincidunt. Nunc efficitur eros ut dolor convallis volutpat. Etiam placerat massa non urna dictum placerat.";
        $content .= "<br/><br/>Cordialement,<br/><br/>";
        $content .= "La boutique Française";

        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande La boutique Française est bien validée.', $content);
    }
}
