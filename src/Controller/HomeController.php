<?php

namespace App\Controller;

use App\Classe\DocCommentsDisplay;
use App\Classe\Mail;
use App\Entity\Header;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
//use GuzzleHttp\Client;

/**
 * classe des tests index()
 */
class HomeController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(SessionInterface $session): Response
    {
        // pour ajouter des produits à mon panier = test
        /*  $session->set('cart', [
                [
                    'id'=> 522,
                    'quantity' => 12
                ]
            ]);
            */
            /* $session->remove('cart');
            $cart = $session->get('cart');

            dd($cart); */

        // test de phpdoc controller DocdocumentDisplay.php
        // AF Regler le pb $class (string en objet)
        // $docphp = new DocCommentsDisplay($this->entityManager);
        //         // $class = new StripeController($this->entityManager);
        // $docphp->AffichageDocComments('StripeController');  //$class);

        // Test mailjet : mail.php
        // $mail = new Mail();
        // $mail->send('solined.independant@gmail.com', 'ED', 'mon premier mail', "Bonjour ED, j'espère que tu vas.");

        //phpinfo();
        
        $products   = $this->entityManager->getRepository(Product::class)->findByIsBest(1);
        $headers    = $this->entityManager->getRepository(Header::class)->findAll();

        return $this->render('home/index.html.twig', [
            //'controller_name' => 'HomeController',
            'products' => $products,
            'headers' => $headers
        ]);
    }
}
