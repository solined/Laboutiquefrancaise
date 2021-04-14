<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * cette classe gère les commandes de l'utilisateur et son affichage lors du paiement.
 * @author soline
 */
class AccountOrderController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/compte/mes-commandes", name="account_order")
     * Affichage de mes commandes
     * //{inheritdoc} 
     *   phpdoc -d .
     * @version : vidéo 61 : ajout de statut  ds  $nom_col
     */
    public function index(): Response
    {
        $table_order_paid_desc  = null;
        $head_table             = null;
        /*    $names = array_unique(array_filter(array_map('trim', explode(',', $string))));
            $tags = $this->manager->getRepository('TagBundle:Tag')->findBy([
            'name' => $names
            ]);
            $newNames = array_diff($names, $tags);
            dd($newNames);
            foreach ($newNames as $name) {
                $tag = [];
                $tag->setName($name);
                $tags[] = $tag;
            }
            dd($tags); 
            */
        //$orders = $this->entityManager->getRepository(Order::class)->findAll();// find(1); //->findOneByStripeSessionId($stripeSessionId);
        $orders = $this->entityManager->getRepository(Order::class)->findSuccessOrders($this->getUser());
        
        if ($orders != null) {
            $nom_col    = array("Référence", "Statut", "Passée le", "Produit(s)", "Total quantité", "Total");
            $head_table = $this->createHeadTableforTwig($nom_col);
            
            // Récupère table des données
            $table_orders_details = $this->createDataTableOfOrders($orders);

            // Structure table with données
            $table_order_paid_desc = $this->createBodyTableOrderForTwig($table_orders_details, false);     //$this->createTableForTwigTest();  //$orders);
        }

        // $orders et table_order_paid_desc  sont vérifié ds la vue
        return $this->render('account/order.html.twig', [
            'controller_name' => 'AccountOrderController',
            'orders' => $orders,
            'table' => $table_order_paid_desc, //$table_orders_details,//
            'tablehead' => $head_table
        ]);
    }

    /**
     * @Route("/compte/mes-commandes/{reference}", name="account_order_show")
     * voir la commande
     * @todo on devrait verifier l'id order existe en bdd  puis le user email s'il correspond
     * @todo AF
     */
    public function show(string $reference)
    {
        $nom_col = [];
        //dump($this);
        //\dump($this->getCarrierName());

        $order = $this->entityManager->getRepository(Order::class)->findOneByReference($reference);
        
        // Sécurité vérifie l'utilisateur et la commande non null 
        // todo
        if (!$order || $order->getUser() != $this->getUser()) {
            return $this->redirectToRoute('account_order');
        }
        
        //prepa tableau : structure head + données
        $nom_col            = array("Produit", "Quantité", "Prix unitaire", "Total");
        $head_table         = $this->createHeadTableforTwig($nom_col);                          //(null, null);
        //echo is_null($head_table); //? 'Null' : $head_table;
        
        //prepa tableau : structure body + données
        $products           = $order->getOrderDetails(); 
        $table_order_detail = $this->createBodyTableOrderShowForTwig($products);                //createTableForTwigTest();
       
        return $this->render('account/order_show.html.twig', [
            'controller_name' => 'AccountOrderController',
            'order' => $order,
            'tablehead' => $head_table,
            'table' => $table_order_detail
        ]);
    }

    /**
     * Crée la tête d'un tableau twig/html via une string
     * use for show() et index() : Tableau des produits de la commande
     * *****************
     * @param array     $nom_col    : nom des colonnes titres
     * @return string   $table_string : le tableau html/twig créé
     * @todo exception
     */
    private function createHeadTableforTwig($nom_col=[]):string  //obligé de mettre un retour
    {
        $table_string = null;
        
        if ($nom_col != null) {       //!empty  ne f° pas des masses

            $table_string = '<tr>'."\n";    
            foreach ($nom_col as $key => $col) {
                $table_string .= "\t".'<th scope="col">'.$col.'</th>'."\n";
            }
            $table_string .= '</tr>';

        }  else {   //ça retourne null
            //exception     //todo
            //$this->exception();
            dd("erreur 404 : createHeadTable");
        }

        return $table_string;
    }

    /**
     * Créer le tableau des données commandes 
     * use for index() : Tableau des commandes
     * ********************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video <61
     * @param object $orders : l'objet commandes
     * @return array $table_order_details : tableau des données des commandes
     * *************
     * @version Version20210301074205 : video 61
     * ajout de $state
     */ 
    private function createDataTableOfOrders($orders)
    {
        $table_order_details    = [];
        $nom_col                = [];
      
        //table des données
       foreach ($orders as $key => $order) {
            //$this->afficheElementForDebug($order);
            $total      = ($order->getCarrierPrice()/ 100) + ($order->getTotal()/ 100);
            $total      = number_format($total, 2, ',', ' ');
            $date_order = date_format($order->getCreatedAt(),'d/m/Y');  //'d/m/Y H:i:s'
            $products   = $order->getOrderDetails();

            $total_quantity = 0;
            foreach ($products as $key => $product) {
                $total_quantity += $product->getQuantity();
            }

            $state      = $order->getState();
            if ($state == 1) {
                $state_str = "Paiement accepté";
            } elseif ($state == 2) {
                $state_str = "Préparation en cours";
            } elseif ($state == 3) {
                $state_str = "Livraison en cours";
            } elseif ($state == 4) {
                $state_str = "Livrée";
            }

            $nom_col = array($order->getReference(), $date_order, count($order->getOrderDetails()), $total, $total_quantity, $state_str);
            $table_order_details[] = $nom_col;
        }
        //echo "<pre>"; print_r($table_order_details); 

        return $table_order_details;
    }

    /**
     * Crée le corps d'un tableau twig/html via une string
     * avec les données détaillée des commandes
     * use for index() : Tableau des produits de la commande
     * *******************************************************
     * @param array de array de string $order_details : tableau des commandes payées
     * @param bool $standard = true : pour créer un tableau html ave balise basique (sans css)
     * @return string $table_string : string du table au format html / sinon null
     * @todo : a verifier si c'est correct sur un array d'array ?
     * @todo : est ce pareil que $orders_details != null    ???????????
     * @todo  : RETESTER les IF
     * **********
     * @version Version20210301074205 : video 61
     * ajout de state
     */
    private function createBodyTableOrderForTwig(array $orders_details=[], bool $standard=false)
    {
        $table_string   = null;
        $vide           = false;
        $ordre_element  = array(0,5,1,2,4,3);       //array(0,1,2,4,3); // si les données ne sont pas ds l'ordre à afficher : préciser

        // Vérifie que toutes les données du tableau sont non vide         
        //$orders_details = array(array("poerut", "kekeek", "jejeejej", "ekekek"), null, array("fsdffd", "fsfsd","dqsfssd","fsdqfsd"));
        $vide = $this->TestIfTableNonEmpty($orders_details);    //@todo : a verifier si c'est correct sur un array d'array ?  

        // securité : verifie le param d'entrée
        if ($orders_details != null && $vide == false) {
             
            //parcours l'array d'array
            foreach ($orders_details as $key => $order) {
                
                // Vérifie que toutes les données sont non vide / parse l'array
                $vide = $this->TestIfTableNonEmpty($order);

                // securité : //parcours l'array
                if ($order != null && $vide === false) {         //retester

                    $lien_html_format = "\t".'<td class="text-right"><a href="mes-commandes/'.$order[0].'">Voir ma commande</a></td>'."\n";     //exemple :  http://127.0.0.1:8000/compte/mes-commandes/03032021-603ff25351ae6

                    if ($standard === true) {
                        // Remplit le tableau html sans css
                        $table_string .= $this->createTableBodyHtmlWithArray($order, $lien_html_format, $ordre_element);
                    } else {
                        // Remplit le tableau html avec du css
                        $td_css_text = '<span class="badge badge-secondary">';
                        $pos = 0;

                        $table_string .= '<tr>'."\n";
                        $table_string .= $this->createTableBodyHtmlWithArray($order, $lien_html_format, $ordre_element, $td_css_text, $pos);
                    }

                    // sécurité : de retour tableau non vide
                    if ($table_string === '<tr>'."\n".'</tr>'."\n"){    //|| $vide == true) {  //retester  doublon $vide
                        $table_string == null;
                        // dd("erreur 404 : createBodyTableForTwig"."\n"."Le tableau string est vide");
                    }
                } /* else {
                    dd("le tableau est vide");
                } */
            }
        } /* else {
            // si les commandes sont vides : l'utilisateur n'a jamais passé de commandes
            //return $this->render('account/index.html.twig');  //pourquoi ca m'affich vide?
            //return $this->redirectToRoute('account');       //compte/mes-commandes
            //exception     //todo
            //$this->exception();
            dd("erreur 404 : createBodyTableForTwig"."\n"."Le tableau est vide");
        } */

        return $table_string;
    }

    /**
     * Tester que toutes les données d'un tableau soient non vide
     ********************
     * @param array $table_a_tester : tableau à tester
     * @return bool $vide =true : s'il manque une donnée / si 1 données est vide
     */
    public function TestIfTableNonEmpty($table_a_tester = null)
    {
        $vide = false;

         // vérifie que toutes les données sont là : non vide
        for ($i=0; $i < count($table_a_tester); $i++) {
            if ($table_a_tester[$i] == null) {
                $vide = true;
                return $vide;
                dd("erreur 404 : createBodyTableForTwig"."\n"."Il manque des données au tableau  order");
            }
        }
        return $vide;
    }

    /**
     * Crée le corps d'un tableau twig/html via une string
     * standard : sans css
     * ******************
     * @param array de string $tab : tableau des commandes payées --> tester non vide avant l'appel à cette fontion
     * @param string $lien : lien à ajouter au tableau html
     * @param array $ordre : ordre dans lequel ajouter les éléments du tableau  -> si les données ne sont pas ds l'ordre à afficher : préciser
     * @return string $string_tab : string du table au format html
     * @todo dev precaution --> tester le tableau non vide avant d'être passé en paramètre  : TestIfTableNonEmpty()
     * *********************
     * @todo vérifier que ce soit les bonnes données : ref, date, avec le type par exemple
     * @todo mettre  $css_text + $position ss forme de tableau (si diff pour chaque td)
     */
    private function createTableBodyHtmlWithArray(array $tab, string $lien_html_format=null, array $ordre=null, string $css_text=null, int $pos=null)
    {
        $string_tab=null;
        //$tab = array("", "", "", "", "");     test

        //$vide = $this->TestIfTableNonEmpty($tab);
        $string_tab .= '<tr>'."\n";
        if ($ordre != null) {
            foreach ($ordre as $key => $indice) {

                if ($css_text != null && $pos !== null && ($indice === $pos)) {
                    $string_tab .= "\t".'<td>'.$css_text.$tab[$indice].'</td>'."\n";
                } else {
                    $string_tab .= "\t".'<td>'.$tab[$indice].'</td>'."\n";
                }
            }
        } else {
            // Rempli la string
            
            foreach ($tab as $i => $string) {
                //$string = null;                   test
                if ($string != null) {
                    $string_tab .= "\t".'<td>'.$string.'</td>'."\n";
                } //else verifier si c'est les bonne données ??? @todo
            }
        }

        /*// Vérifie que la string ne soit pas vide       -> fait ds le main
          if ( $string_tab === '<tr>'."\n" || $vide === true) {    //test faits ci-dessus
            dd("Erreur 404 : createTableHtmlWithDataArray"."\n"."Le tableau string est vide");
        } */

        // Finit de remplir la string
        if ($lien_html_format) {
            $string_tab .= $lien_html_format;
        }
        $string_tab .= '</tr>'."\n";

        return $string_tab;
    }

     /**
     * Crée le corps d'un tableau twig/html via une string
     * avec les données détaillée d'une commande : produits
     * use for show() : Tableau des produits de la commande
     * ************************
     * @param array de string $products : les produits de la commande
     * @return string $table_string : string du table au format html
     */
    private function createBodyTableOrderShowForTwig($products)
    {
        // Construction du tableau avec les données
        $table_string = null;

        if ($products != null) {   //securité
            foreach ($products as $key => $product) {
                    //$this->afficheElementForDebug(null, $product);
                    $price = number_format($product->getPrice()/100, 2, ',', ' ');
                    $total = number_format($product->getTotal()/100, 2, ',', ' ');
                    $table_string .= '<tr>'."\n";    
                    $table_string .= "\t".'<td>'.$product->getProduct().'</td>'."\n";
                    $table_string .= "\t".'<td>'.$product->getQuantity().'</td>'."\n";
                    $table_string .= "\t".'<td>'.$price.'</td>'."\n";
                    $table_string .= "\t".'<td>'.$total.'</td>'."\n";
                    $table_string .= '</tr>'."\n";
            }
        }  else {   //ça retourne null
            //exception     //todo
            //$this->exception();
            dd("erreur 404 : createBodyTableOrderShowForTwig"."\n"."Le tableau est vide");
        }

        return $table_string;
    }

    /**
     * Affichage d'élément pour Débogage Développeur : order
     * *****************
     * @param object $order : l'objet
     * @param object $product : l'objet
     */
    private function afficheElementForDebug($order=null, $product=null)
    {
        if ($order) {
            dump($order->getReference());
            dump($order->getCreatedAt());
            dump(count($order->getOrderDetails()));
            dump(number_format($order->getCarrierPrice()/ 100, 2, ',', ','));
            dump(number_format($order->getTotal()/ 100, 2, ',', '.')); 
            print_r(date_format($order->getCreatedAt(),'d/m/Y H:i:s'));
        }

        if ($product) {
            dump($product->getProduct());
            dump($product->getQuantity());
            dump($product->getPrice());
            dump($product->getTotal());
        }
        dd("fin affichage element pour TestDev");
    }

    //fonction test
    private function createTableForTwigTest($var = null)
    {
        $table_order_paid_desc = [];
       
        $table_order_paid_desc = '<tr>
                    <th scope="row">1</th>
                    <td>Mark</td>
                    <td>Otto</td>
                    <td>@mdo</td>
                 </tr>';

        return $table_order_paid_desc;
    }

   /*  public function exception(){
        try {
            throw new Exception('foo');
        } catch (Exception $e) {
            return 'catch';
        } finally {
            return 'finally';
        }
    } */

}
