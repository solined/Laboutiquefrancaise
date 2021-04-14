<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Durand Soline <Solined.independant@php.net>
 * @version Version20210301074205 : video 52
 * 
 * lié à add.html.twig, OrderController
 * 
 * @version Version20210301074205 : video 54 sd
 * creation d'un constructeur & entitymanager
 */
class StripeController extends AbstractController
{
    private $products_for_stripe;
    private $YOUR_DOMAIN;
    private $checkout_session;
    private $entityManager;

    /** @version Version20210301074205 : video 54 sd
     * creation d'un constructeur entitymanager
     * injecte l’entity manager de Doctrine dans un controller.
     * injecte les dépendances en passant ces derniers en paramètres du constructeur de votre controller
     */
     public function __construct(EntityManagerInterface $entityManager)
    {
        $this->checkout_session     = new Session();                        //objet Stripe/checkout/session
        $this->YOUR_DOMAIN          = 'http://127.0.0.1:8000/';             // à modifier en production
        $this->products_for_stripe  = [];
        $this->entityManager        = $entityManager;

        // dd($this->entityManager->getRepository(Order::class)->findAll());
    }

    /**
     * @Route("commande/create-session/{reference}", name="stripe_create_session")
     *************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * @param Cart $cart : panier des produits  validé
     * methode stripeTableOfProduit($cart)
     * function index(Cart $cart)
     * 
     * @return JsonResponse $response : id de session appellé dans bouton payer (add.html.twig) et checkoutButton (script)
     * @version Version20210301074205 : video 53
     * @param Order $reference : reference de la livraison (recup ds order)
     * @param EntityManagerInterface $entityManager : appellée avec fetch (ds la vue add), ss constructeur, en injection de dépendance 
     * @return Jsonresponse error : secutité exception faite
     * method stripeTableOfProduitByOrder($order), $entityManager)
     * method stripeCarrierInfo($order)
     * function index($reference, entityManagerInterface $entityManager)
     * 
     * @version Version20210301074205 : video 54 sd
     * @param Order $reference : reference de la livraison (recup ds order)
     * creation d'un constructeur et entitymanager
     * method stripeTableOfProduitByOrder($order)
     * function index($reference)
     * @todo metter la partie transporteur dans une function
     */
    public function index($reference)
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByReference($reference);

        if (!$order) {
            new JsonResponse(['error' => 'order']);
        }

        $this->stripeTableOfProduitByOrder($order);

        // enregistrement des info transporteur
        $this->stripeCarrierInfo($order);

        // Crée la session Stripe
        $this->stripeCheckoutSession();

        // Enregistre la session id : pour le paiement validé
        $order->setStripeSessionId($this->checkout_session->id);
        $this->entityManager->flush();

        // Renvoie la session id au script de add.html.twig : pr etre redirigé (sessionId)
        $response = new JsonResponse(['id' => $this->checkout_session->id]);            //echo json_encode(['id' => $checkout_session->id]);

        return $response;
    }

    /* public function stripeBase()
    {
        Stripe::setApiKey('sk_test_51IOjoIG4xCSY9MmYsyPDjRVr73wX6Znkc3b8bEIQloche5p4714zBZEamzirCecu2dZLQkzkfe0XaaYDIasFCk7l00QMkeMxPW');
        
        // $YOUR_DOMAIN = 'http://127.0.0.1:8000/';
        
        $checkout_session = Session::create([                       //\Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        //produit : stripe fait le total tout seul
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => 2000,
                'product_data' => [
                    'name' => 'Stubborn Attachments',
                    'images' => ["https://i.imgur.com/EHyR2nP.png"],
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        //redirection
        'success_url' => $this->YOUR_DOMAIN . '/success.html',
        'cancel_url' => $this->YOUR_DOMAIN . '/cancel.html',
        ]);

        //echo json_encode(['id' => $checkout_session->id]);

        dump($checkout_session->id);
        dd($checkout_session);
    } */

    /**
     * Enregistrer un tableau des produits pour Stripe
     * un tableau dans un tableau
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * @param Cart $cart : panier des produits validé
     * return array $products_for_stripe
     * @todo exceptions 
     */
    private function stripeTableOfProduit(Cart $cart)
    {
        $products = $cart->getFull();

        foreach ($products as $product) {
            //array $product
            $product_price      = $product['product']->getPrix();
            $product_quantity   = $product['quantity'];
            $name_illustration  = $product['product']->getIllustration();

            $this->products_for_stripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product_price,
                    'product_data' => [
                        'name' => $product['product']->getName(),
                        'images' => [$this->YOUR_DOMAIN."/uploads/".$name_illustration],
                    ],
                ],
                'quantity' => $product_quantity,
            ];
        } 

        //return true;   //else exception  todo
    }
    
    /**
     * Enregistrer un tableau des produits pour Stripe
     * un objet dans un tableau
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 53  : stripeTableOfProduit différent
     * @param Order $order :
     * @param EntityManagerInterface $entityManager
     * return array $products_for_stripe
     * @todo exceptions ; mettre en global entityManager ?
     */ 
    private function stripeTableOfProduitByOrder(Order $order) {     //}, EntityManagerInterface $entityManager) {
        
        foreach ($order->getOrderDetails()->getValues() as $product) {
            //objet $product
            $product_price      = $product->getPrice();
            $product_quantity   = $product->getQuantity();
            $product_name       = $product->getProduct();
            $product_object     = $this->entityManager->getRepository(Product::class)->findOneByName($product_name);
            $name_illustration  = $product_object->getIllustration();
            
            $this->products_for_stripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product_price,
                    'product_data' => [
                        'name' => $product_name,
                        'images' => [$this->YOUR_DOMAIN."/uploads/".$name_illustration],
                    ],
                ],
                'quantity' => $product_quantity,
            ];
        }
    }

    /**
     * Crée la session Stripe : variable de paiement
     * Ce qu'on passe à l'API STRIPE
     *************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * return array session $checkout_session or exception AF
     * @todo exceptions : à finir
     * @version Version20210301074205 : video 53
     * ajout d'email
     * @version Version20210301074205 : video 54
     * redirection : paiement validé ou non (retour vers laboutiquefrancaise)
     * @param string CHECKOUT_SESSION_ID : stripeSessionId  : quand on valide le paiement stripe
     * changement de nom de F° stripeLBF en stripeCheckoutSession
     */
    private function stripeCheckoutSession()
    {
        Stripe::setApiKey('sk_test_51IOjoIG4xCSY9MmYsyPDjRVr73wX6Znkc3b8bEIQloche5p4714zBZEamzirCecu2dZLQkzkfe0XaaYDIasFCk7l00QMkeMxPW');

        $this->checkout_session = Session::create([                                                          //\Stripe\Checkout\Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],                                                              //'customer_details' => $this->getUser()->getLastname(),  // "billing_details": {   "name" }  [1]
               
            // produit : stripe fait le total tout seul
            'line_items' => [$this->products_for_stripe],
            'mode' => 'payment',

            // redirection : paiement validé or not
            'success_url' => $this->YOUR_DOMAIN . 'commande/merci/{CHECKOUT_SESSION_ID}',                   //'/success.html',
            'cancel_url' => $this->YOUR_DOMAIN . 'commande/erreur/{CHECKOUT_SESSION_ID}'                    //'/cancel.html',  //retour vers laboutiquefrancaise
        ]);

        // dump($this->checkout_session->id);
        //dd($this->checkout_session);

        if (!empty($this->checkout_session))
            return true; 
        else 
            return false;   // exception todo
    }

    /**
     * Continue le tableau pour stripe en ajoutant le transporteur choisit
     *************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 53
     * @version Version20210301074205 : video 54
     *  MAJ du prix transporteur en Admin/backoffice et Carrier __toString() :suppr le *100
     * @todo exceptions : à finir
     */
    private function stripeCarrierInfo(Order $order)
    {
        $this->products_for_stripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName(),
                    'images' => [$this->YOUR_DOMAIN], //."/uploads/".$name_illustration],
                ],
            ],
            'quantity' => 1,
        ];
    }
}
