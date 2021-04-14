<?php

namespace App\Controller\Admin;

use App\Classe\Mail;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * Gestion des commandes côté backoffice :
 * Affichage des commandes de la plus récentes à la moins récentes,
 * Affiche les détails d'une commande,
 * Configure les actions du systeme de livraison,
 * Edit une commande,
 * Supprime une commande.
 * 
 * @version vidéo 61 
 * @author Durand Soline <Solined.independant@php.net>
 */
class OrderCrudController extends AbstractCrudController
{
    private $entityManager;
    private $adminUrlGenerator;     //private $crudUrlGenerator;
    private $adminContextProvider;

    /**
     * Constructor of OrderCrudController class
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 video 61 : remplace CrudUrlGenerator par AdminUrlGenerator
     * @param EntityManagerInterface    $entityManager          datas of doctrine
     *  param CrudUrlGenerator          $crudUrlGenerator       generator of url for update... function to return
     * @param AdminUrlGenerator         $adminUrlGenerator      generator of url for update... function to return
     * @param AdminContextProvider      $adminContextProvider   Injecte les données du contexte du dashboard, concernant la commande.
     * 
     * @return void
     */
    public function __construct(EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator, AdminContextProvider $adminContextProvider)
    {
        $this->entityManager        = $entityManager;
        $this->adminUrlGenerator    = $adminUrlGenerator;       //$this->crudUrlGenerator     = $crudUrlGenerator;
        $this->adminContextProvider = $adminContextProvider;
    }

    /**
     * ?
     */
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    /**
     * Configure l'ordre d'affichage des commandes dans le dashboard
     * 
     * @version vidéo 54 25:20
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => 'DESC']);
    }

    /**
     * Affichage du détail de la commande choisie dans le Dashboard
     * , "voir" ds dashboard
     * 
     * L'affichage (admin) fonctionne très bien sans cette f°
     * order : ID, Create AT, CarrierName, CarrierPrice, IsPaid
     * intéressant pour personnalisé les titres des colonnes. Et ajouter celle de orderDetails
     * 
     * @param string $pageName   action detail configureActions()
     * @return FieldInterface[]
     * @see https://symfony.com/doc/current/bundles/EasyAdminBundle/fields.html#field-types
     * @see https://symfony.com/doc/current/bundles/EasyAdminBundle/fields.html#unmapped-fields
     */
    public function configureFields(string $pageName): iterable
    {
        return [                                                                                    //Autre possibilité :
            IdField::new('id'),
            // TextEditorField::new('description'),
            DateTimeField::new('createdAt', 'Passée le'),                                           //DateField::new('createdAt')
            TextField::new('user.fullname', 'Utilisateur'),                                         //user.getFullname   //creation ds User.php de la f°
            TextEditorField::new('delivery', 'Adresse de livraison')->onlyOnDetail(),               //video61
            MoneyField::new('total', 'Total produit')->setCurrency('EUR'),                          //getTotal, 'Total'  //creation ds Order.php de la f°
            TextField::new('carrierName', 'Transporteur'),
            MoneyField::new('carrierPrice', 'Frais de port')->setCurrency('EUR'),
            BooleanField::new('isPaid', 'Payée'),
            ChoiceField::new('state')->setChoices([                                                  //video61
                'Non payée' => 0,
                'Payée' => 1,
                'Préparation en cours' => 2,
                'Livraison en cours' =>3,
                'Livrée' => 4
            ]),
            ArrayField::new('orderDetails', 'Produits achetés')->hideOnIndex()
        ];
    }

    /**
     * Actions administrateur sur la vue commande
     * "voir" dashboard ou Commandes
     *
     * @param   Actions $actions        Le statut à modifier de la commande.
     * 
     * @version vidéo 61 : ajout des 3 actions de changement de statut $updatePreparation, $updateDelivery, $updateDeliverySuccess
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function configureActions(Actions $actions): Actions
    {
        /*  //AF   on affiche cette possiblité que si le statut n'est pas déjà en cours de prepa
            ///comment recup les données du context ?
            //if ($state != 2) {
            // dd($this->entityManager->getRepository(Order::class)->findAll());//findOneByReference($reference));//$state = $order->getState();
            // } else {
            //     return $actions
            //     ->add('index', 'detail')
            // }*/

        $updatePreparation      = Action::new('updatePreparation', 'Préparation en cours', 'fas fa-box-open')->linkToCrudAction('updatePreparation');
        $updateDelivery         = Action::new('updateDelivery', 'Livraison en cours', 'fas fa-shipping-fast')->linkToCrudAction('updateDelivery');
        $updateDeliverySuccess  = Action::new('updateDeliverySuccess', 'Livrée', 'fas fa-cart-arrow-down')->linkToCrudAction('updateDeliverySuccess');

        // dans l'ordre d'affichage
        return $actions
            ->add('detail', $updateDeliverySuccess)
            ->add('detail', $updateDelivery)
            ->add('detail', $updatePreparation)
            ->add('index', 'detail');                 //possible avec constante crud index, details... si on veut modifier le nom des pages ; ici plus simple
    }

    /**
     * Change l'état de la commande à "Preparation en cours"
     * 
     * @return RedirectResponse     Redirection classique vers une URL : $url.
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function updatePreparation(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->updateState(2);
    }
   
    /**
     * Change l'état de la commande à "Livraison en cours"
     * 
     * @return RedirectResponse     Redirection classique vers une URL : $url.
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function updateDelivery(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->updateState(3);
    }

    /**
     * Change l'état de la commande à "Livrée"
     * 
     * @return RedirectResponse     Redirection classique vers une URL : $url.
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function updateDeliverySuccess(): \Symfony\Component\HttpFoundation\RedirectResponse
    { 
        return $this->updateState(4);
    }

    /**
     * Met à jour l'état de la commande dans le Dashboard
     * et le notifie.
     * 
     * fonction commune aux actions update du service livraison
     * 
     * @param   int                     $statut     Nouveau statut de la commande
     * 
     * @var     string                  $url        URL de redirection générée.
     * 
     * @return  RedirectResponse        Redirection classique vers une URL : $url.
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function updateState(int $statut): \Symfony\Component\HttpFoundation\RedirectResponse       //, AdminContext $context)
    {
        //die('ok');
        $context        = $this->adminContextProvider->getContext();                //$context    = $this->get(AdminContextProvider::class)->getContext();
        $order          = $context->getEntity()->getInstance();
        $state          = $order->getState();
        $url            = $this->generateUrlforAction();
        $text_statut    = $this->initTextSatut($statut);

        $this->modifyStateInOrder($statut, $order, $state);
        $this->notifyStateChangeToAdmin($statut, $order, $state, $text_statut);
        $this->notifyStateChangeToUserByMail($statut, $order, $state, $text_statut);

        return $this->redirect($url);
    }

    /**
     * Service pour générer des URL dans votre code PHP.
     * anciennement crudUrlGenerator
     * 
     * @var     string  $url    url généré par service AdminUrlGenerator
     * @return  string          Redirection classique vers une URL : $url.
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function generateUrlforAction(): string
    {
        /*  $url        = $this->crudUrlGenerator->build()
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl(); */

        $url = $this->adminUrlGenerator
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $url;
    }
    
    /**
     * Initialise le texte correspondant au statut
     * et une partie du contenu du mail utilisateur
     *
     * @param   int     $statut         Le statut demandé.
     * 
     * @var     string  $text_statut    Le texte correspondant au statut.
     * 
     * @return  array   $text_statut
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    private function initTextSatut(int $statut): string
    {
        $text_statut = null;

        if ($statut == 2) {
            $text_statut    = "en cours de préparation";
        } elseif ($statut == 3) {
            $text_statut    = "en cours de livraison";
        } elseif ($statut == 4) {
            $text_statut    = "Livrée";
        }

        return $text_statut;
    }

    /**
     * Modifie l'état de l'entité commande, en base
     * 
     * Prépare (set) et enregistre (flush) l'entité Order
     * 
     * @param   int             $statut                 Nouvel état de la commande.
     * @param   Order           $order                  L'entité commande du contexte.
     * @param   int             $state                  état actuel de la commande.
     * 
     * @var     entityManager   $this->entityManager    base doctrine
     * 
     * @version Version20210301074205 video 61. notifyAndSendStatusChange
     * @author Durand Soline <Solined.independant@php.net>
     */
    public function modifyStateInOrder(int $statut, Order $order, $state)
    {
        if ($state !== $statut) {
            $order->setState($statut);
            $this->entityManager->flush();
        }
    }

    /**
     * Notifie le changement d'état de la commande, dans le Dashboard.
     * 
     * Affiche la notification à l'administrateur du service livraison.
     * 
     * @param int       $statut             Nouvel état de la commande.
     * @param Order     $order              La commande.
     * @param int       $state              état actuel de la commande.
     * @param string    $text_statut        Html : texte correspondant au nouvel état.
     * 
     * @var string      $ref_order          Le n° de référence de la commande.
     * @var string      $notif_deb          Html : Début de message de notification.
     * @var array       $notif_end          Html : Fin de message de notification.
     * @var array       $notif_color_tab    Html : Couleur des 2 notifications.
     * 
     * @return void
     * 
     * @version Version20210301074205 video 61. notifyAndSendStatusChange
     * @author Durand Soline <Solined.independant@php.net>
     */
    private function notifyStateChangeToAdmin(int $statut, Order $order, int $state, string $text_statut)
    {
        $ref_order          = $order->getReference();
        $notif_deb          = "<strong>La commande ".$ref_order;
        $notif_end          = $this->initEndOfNotif($text_statut);
        $notif_color_tab    = $this->initNotifcolor($statut);

        if ($state !== $statut) {
            $this->addFlash('notice', $notif_color_tab["if"].$notif_deb.$notif_end['notif_if']);
        } else {
            $this->addFlash('notice', $notif_color_tab["else"].$notif_deb.$notif_end['notif_else']);
        }
    }

    /**
     * Initialise la couleur de la notification administrateur
     * 
     * suivant le changement ou non du statut de la commande.
     * 
     * @param   int     $statut             Le statut demandé.
     * 
     * @var     string  $notif_color        Le texte correspondant à la couleur du nouveau statut.
     * @var     string  $notif_color_else   Le texte correspondant à la couleur du même state.
     * @var     array   $notif_color_tab    les 2 textes 
     * 
     * @return  array   $notif_color_tab
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    private function initNotifcolor(int $statut): array
    {
        $notif_color        = null;
        $notif_color_else   = "<span style='color:red;'>";

        if ($statut == 2) {
            $notif_color    = "<span style='color:green;'>";
        } elseif ($statut == 3) {
            $notif_color    = "<span style='color:orange;'>";
        } elseif ($statut == 4) {
            $notif_color    = "<span style='color:blue;'>";
        }

        $notif_color_tab = array("if" => $notif_color, "else" => $notif_color_else);

        return $notif_color_tab;
    }

    /**
     * Initialise la fin de la notification administrateur
     * 
     * suivant le changement ou non du statut de la commande.
     * 
     * @param   string  $text_statut    Le statut de la commande
     * 
     * @var     string  $notif_if       La fin de la notification si changement de statut effectué.
     * @var     string  $notif_else     Le fin de la notification si pas de changement.     Html : Fin de message de notification.
     * @var     array   $notif_tab      des 2 textes de fin.
     * 
     * @return  array   $notif_tab.
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     */
    private function initEndOfNotif(string $text_statut)
    {
        $notif_if   = " est bien <u>".$text_statut."</u>.</strong></span>";
        $notif_else = " <u>est déjà</u> ".$text_statut.".</strong></span>";

        $notif_tab = array("notif_if" => $notif_if, "notif_else" => $notif_else);

        return $notif_tab;
    }

    /**
     * Notifie le changement d'état de la commande à l'utilisateur, par email.
     * 
     * @param   int     $statut         Nouvel état de la commande.
     * @param   Order   $order          L'entité commande du contexte.
     * @param   int     $state          état actuel de la commande. 
     * @param   string  $text_statut    Html : texte correspondant au nouvel état.
     * 
     * @var     string  $notif_user     Html : message envoyé à l'utilisateur.
     * 
     * @return  void
     * 
     * @version Version20210301074205 video 61. notifyAndSendStatusChange
     * @author Durand Soline <Solined.independant@php.net>
     */
    private function notifyStateChangeToUserByMail(int $statut, Order $order, int $state, string $text_statut)
    {
        $notif_user = "Votre commande N°".$order->getReference()." est ".$text_statut;

        if ($state !== $statut) {
            $this->sendEmail($order, $notif_user);
        }
    }

    /**
     * Envoi un email à l'utilisateur
     * 
     * Envoi l'email de notification de changement de statut de commande (state)
     * 
     * @param Order     $order      la commande.
     * @param string    $message    Html : le message à envoyé.
     * 
     * @var Mail        $mail       Mail à envoyé.
     * @var string      $subject    Le sujet du mail.
     * @var string      $content    Html : le contenu du Mail.
     * 
     * @return void
     * 
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 video 61.
     * 
     * @see sendEmailSuccess() ds OrderSuccessController
     * @todo A finir ; à mettre dans un trait car use ds d'autre classe!
     */
    public function sendEmail(Order $order, $message)
    { 
        $mail       = new Mail();
        $subject    = 'Votre commande La boutique Française est bien en cours de livraison.';

        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), $subject, $message);
    }   
}

 /**
     * Initialise, suivant le statut :
     * la couleur de la notification administrateur,
     * et une partie du contenu du mail utilisateur
     *
     * @param   int     $statut         Le statut demandé.
     * 
     * @var     string  $notif_color    Le texte correspondant à la couleur.
     * @var     string  $text_statut    Le texte correspondant au statut.
     * @var     array   $tab            des 2 textes initialisés.
     * 
     * @return  array   $tab
     * 
     * @version Version20210301074205 video 61.
     * @author Durand Soline <Solined.independant@php.net>
     * @todo a decouper par action ?
     */
    /* public function initNotifcolorAndMessagestatut(int $statut)
    {
        $notif_color        = null;
        $text_statut        = null;
        $notif_color_else   = "<span style='color:red;'>";

        if ($statut == 2) {
            $notif_color    = "<span style='color:green;'>";
            $text_statut    = "en cours de préparation";
        } elseif ($statut == 3) {
            $notif_color    = "<span style='color:orange;'>";
            $text_statut    = "en cours de livraison";
        } elseif ($statut == 4) {
            $notif_color    = "<span style='color:blue;'>";
            $text_statut    = "Livrée";
        }
        //  switch ($statut) {
        //     case 2:
        //         $notif_color    = "<span style='color:green;'>";
        //         $text_statut    = "en cours de préparation";
        //         break;
        //     case 3:
        //         $notif_color    = "<span style='color:orange;'>";
        //         $text_statut    = "en cours de livraison";
        //         break;
        //     case 4:
        //         $notif_color    = "<span style='color:blue;'>";
        //         $text_statut    = "Livrée";
        //         break;
        // }
        $tab = array("if" => $notif_color, "text_statut" => $text_statut, "else" => $notif_color_else);

        return $tab;
    } */

    /**
     * Notifie le changement d'état de la commande, à $statut : Html.
     * 
     * Affiche la notification à l'administrateur du service livraison,
     * et Envoie un mail à l'utilisateur.
     * 
     * @param int       $statut                     Nouvel état de la commande.
     * @param Order     $order                      L'entité commande du contexte.
     * 
     * @var int         $state                      état actuel de la commande.
     * @var string      $text_statut                Html : texte correspondant au nouvel état de la commande.
     * @var array       $tab                        Html : couleur de la notif et texte du statut.
     * @var string      $notif_deb                  Html : Début de message de notification.
     * @var array       $notif_end                  Html : Fin de message de Notification.
     * @var string      $notif_user                 Html : Contenu du message du mail envoyé à l'utilisateur.
     * 
     * @return void
     * 
     * @version Version20210301074205 video 61. notifyAndSendStatusChange puis decoupage
     * @author Durand Soline <Solined.independant@php.net>
     */
    /*  public function notifyStateChangetoAdminAndUser(int $statut, Order $order)
    {
        $state              = $order->getState();
        $notif_deb          = "<strong>La commande ".$order->getReference();
        $text_statut        = $this->initTextSatut($statut);
        $notif_color_tab    = $this->initNotifcolor($statut);
        $notif_end          = $this->initEndOfNotifText($text_statut);       
        $notif_user         = "Votre commande N°".$order->getReference()." est ".$text_statut;

        if ($state !== $statut) {
            // Prépare et enregistre l'entité
            $order->setState($statut);
            $this->entityManager->flush();

            // Notifie l'administrateur du service livraison
            $this->addFlash('notice', $notif_color_tab["if"].$notif_deb.$notif_end['notif_if']);
            
            //$this->sendEmail($order, $notif_user);
        } else {
            $this->addFlash('notice', $notif_color_tab["else"].$notif_deb.$notif_end['notif_else']);
        }
    } */

    /* public function function_action()
    {
        //recup le nom de la fonction executée
        //dd($this->func_name);
        return $this->__call('updateState', 4);
        // if ($this->action === 4) {
        //     //return $this->updateState(4);
        //     //return call_user_func(array($this, 'updateState'), 4);

        //     //$this->function = 
        //     return $this->__call('updateState', 4);
        //     //dd($this->function);

        //     // $function = new ReflectionFunction('updateState');
        //     // echo $function->invoke(4);
        // } elseif ($this->action === 3) {
        //     return $this->__call('updateState', 3);
        // } elseif ($this->action === 2) {
        //     return $this->__call('updateState', 2);
        // }
    } */

    /**
     * Methode magique __call()
     * 
     *  En appelant directement la méthode voler(), la variable $arguments sera un array stockant les différents arguments.
     *  A contrario, si vous passez par la méthode __call(), le second argument sera du type que vous voudrez.
     * $george->voler('Afrique');
     * $georges->__call('voler','Afrique');
     * 
     * empêche tout d'abord la génération automatique de documentation de code au moyen des APIs 
     * (PHPDocumentor par exemple) utilisant les objets d'introspection (Reflection)
     * 
     * @param string $method Nom de la méthode à appeler
     * @param array $arguments Tableau de paramètres
     * @return void
     */
   /*  public function __call($method, $args) //$method, $args = array())
    {
        if (is_callable(array($this, $method))){
            printf("Bonjour %s\r\n", $args);
            //return call_user_func_array(array($this, $method), $args);
            //if($method === 'update')
            return call_user_func(array($this, $method), $args);
        }
        printf("bad %s\r\n", $args);
    } */