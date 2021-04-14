<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Address;
use App\Entity\Carrier;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Durand Soline <Solined.independant@php.net>
 * @version Version20210301074205 : video 52
 * fille de Order
 */
class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande", name="order")
     * 
     * Affichage du formulaire de notre commande (je passe ma commande) :
     *  choisir 1 adresse et 1 transporteur
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 50? <52
     * @param Cart $cart : mon panier validé
     * 
     * @return form $form : formulaire de la caommande
     * @return Cart $cart : mon panier validé
     */
    public function index(Cart $cart): Response
    {
        if (!$this->getUser()->getAddresses()->getValues()) {
            return $this->redirectToRoute('account_address_add');
        }

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        // dump() dans order/index.twig 
        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getFull()
        ]);
    }
    
    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST"})
     * 
     * Création de notre commande en bdd : valider le récapitulatif "payer"
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * @param Cart $cart : mon panier validé
     * @param Request $request : la requête POST du formulaire de commande
     * *************
     * @return Cart $cart : mon panier validé
     * @return String $carriers : mon transporteur (infos)
     * @return String $delivery_content : mon adresse choisie pour la commande
     * else 
     * @return rediredirectToRoute cart : redirige vers mon panier à valider
     * ************
     * @version Version20210301074205 : video 61 : ajout de setState
     * 
     * @todo vérifier les exceptions ; test STRIPE à suppr ;
     */
    public function add(Cart $cart, Request $request): Response
    {
        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);
        
        // écoute la requete stockée en injection de dépendance
        $form->handleRequest($request);
        
        // Recuperer les variables POST et les enregistrer en base
        if ($form->isSubmitted() && $form->isValid()) { //dd($form->getData());
            
            //recuperer les variables POST, initialisation
            $carriers           = $form->get('carriers')->getData();
            $delivery           = $form->get('addresses')->getData();           //AppEentity/Address

            $delivery_content   = $this->transformDeliveryInString($delivery);  //simplifier code en 1 ligne
            
            //Enregistrer ma commande Order()
            $order = $this->saveMyOrder($carriers, $delivery_content);         //simplifier code en 1 ligne
            // dump($this->entityManager); //ya rien!?
            // dd($order->getReference());

            /* //Envoyer a stripe mon produit sous forme de tableau */
            
            //Enregistrer mes produits dans OrderDetails()
            $orderDetails = $this->saveMyOrderDetails($cart, $order);           //simplifier code en 1 ligne
            
            //enregistre en BDD
            $this->entityManager->flush();                                      //en attendant les tests dev

            //STRIPE TEST
                // $stripe = new StripeController;
                // $stripe->stripeTableOfProduit($cart);
                // $test = $stripe->stripeLBF(); //stripeBase();
                // dd($test);
            //FIN STRIPE  
           
            return $this->render('order/add.html.twig', [
                'cart' => $cart->getFull(),
                'carrier' => $carriers,
                'delivery' => $delivery_content,                                /* , 'checkout_session' => $this->checkout_session->id //pour STRIPE  */
                'reference' => $order->getReference()
            ]);
        }

        return $this->redirectToRoute('cart');
    }

    /**
     * incorpore un separateur dans une string
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video <52
     * @param string $str : la chaine de caractère
     * @param string $separator : le(s) caractère(s) à ajouter à ma string
     * @return string $string : ma chaîne de caractères transformées 
     * 
     * @todo je peux mettre ça dans une library à moi ?
     */
    public function transformInString(string $str='', string $separator='')
    {
        $string = $separator.$str;

        return $string;
    }

    /**
     * transformer un objet en string
     * comment mettre Entity à la place d'objet : pr etre réutilisable?
     * et appeller une library perso pour les f° générique
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * @param Address $delivery : mon adresse choisie
     * 
     * @return string $delivery_content : mon objet adresse transformé en string 
     * @todo exception
     */
    public function transformDeliveryInString(Address $delivery)    //, string $separator='')
    {     
            //méthode 2
            $delivery_content = $this->transformInString($delivery->getFirstname());
            $delivery_content .= $this->transformInString($delivery->getLastname(), ' ');
            $delivery_content .= $this->transformInString($delivery->getPhone(), '<br/>');
            //option facultative
            if ($delivery->getCompany()) {
                $this->transformInString($delivery->getCompany(), '<br/>');
            }
            $delivery_content .= $this->transformInString($delivery->getAddress(), '<br/>');
            $delivery_content .= $this->transformInString($delivery->getPostal(), '<br/>');
            $delivery_content .= $this->transformInString($delivery->getCity(), ' ');
            $delivery_content .= $this->transformInString($delivery->getCountry(), '<br/>');
        
            //methode base
            /* 
                $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
                $delivery_content .= '<br/>'.$delivery->getPhone();
                if ($delivery->getCompany()) {
                    $delivery_content .= '<br/>'.$delivery->getCompany();
                }
                $delivery_content .= '<br/>'.$delivery->getAddress();
                $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
                $delivery_content .= '<br/>'.$delivery->getCountry(); */

        return $delivery_content;

        /* if (!empty($delivery_content))
                return true; 
            else 
                return false;   // exception todo */
    }

    /**
     * Enregistre ma commande Order() ds entityManager : A partir des données du formulaire
     * elle est non payée ( pr les test isPaid(0) )
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * @param Carrier $carriers : le transporteur choisit par l'utilisateur
     * @param string $delivery_content : l'adresse utilisateur chosie
     *  
     * @return Order $order : mon objet Order enregistré ds entityManager et à enregistrer en bdd
     * 
     * @todo exception
     */
    public function saveMyOrder(Carrier $carriers, String $delivery_content) 
    {
        $date       = new \DateTime();
        $order      = new Order();
        $reference  = $date->format('dmY').'-'.uniqid();        //reference livraison pour stripe

        $order->setReference($reference);
        $order->setUser($this->getUser());
        $order->setCreatedAt($date);
        $order->setCarrierName($carriers->getName());
        $order->setCarrierPrice($carriers->getPrice());         //n'est pas bon

        $order->setDelivery($delivery_content);
        $order->setIsPaid(0);                                   //commande non payée à cet instant (DateTime)
        $order->setState(0);                                    //pour le systeme de livraison // non payée
        //dd($order->getTest()); //null
        
        $this->entityManager->persist($order);                  //enregistre ds l'objet entityManager
 
        return $order;
    }

    /**
     * Enregistrer mes produits dans OrderDetails()
     ************************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 52
     * @param Cart $cart : Mon panier validé
     * @param Order $order : la commande utilisateur
     *  
     * @return OrderDetails $orderDetails : mon objet OrderDetails enregistré ds entityManager et à enregistrer en bdd
     * 
     * @todo exception
     * @todo changer le nom de la F° : saveProductsInOrderDetails() ?
     */
    public function saveMyOrderDetails(Cart $cart, Order $order) 
    {
        $products = $cart->getFull();

        foreach ($products as $product) {
            $orderDetails       = new OrderDetails;
            $product_price      = $product['product']->getPrix();
            $product_quantity   = $product['quantity'];

            $orderDetails->setMyOrder($order);
            $orderDetails->setProduct($product['product']->getName());
            $orderDetails->setQuantity($product_quantity);
            $orderDetails->setPrice($product_price);
            $orderDetails->setTotal($product_price * $product_quantity);

            $this->entityManager->persist($orderDetails);

            /* enregistrer un tableau des produits pour stripe (intérieur foreach stripeTableOfProduit() ) */
        }
        
        if ($orderDetails) {
            return $orderDetails;
        } else {    //return booleen ou exeception ou 404 error : todo
            dump($orderDetails);
            dd("what - orderDetails failed");
            /* return $this->render('error/index.html.twig']);  //error page */
        }
    }
}
