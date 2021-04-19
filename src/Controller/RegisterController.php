<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
//interface
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegisterController extends AbstractController
{
    private $entityManager;

    /**
     * AccountPasswordcontroller constructor
     * @param $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/inscription", name="register")
     * Inscription utilisateur
     * *************************
     * @param Request $request : requete http
     * @param serPasswordEncoderInterface $encoder : encode le mp en bdd
     * @return render register/index.html.twig $form, $notification
     * envoi un mail de notification
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $notification   = null;
        $user           = new User();                                       //j'instancie ma class user
        $form           = $this->createForm(RegisterType::class, $user);    //je créer le fichier RegisterType lié à l'entité user

        // teste la requete entrante (ce que rempli le user)
        $form->handleRequest($request);

        // Vérifie que le formulaire soit soumis et valid
        if ( $form->isSubmitted() && $form->isValid() ) {
            // injecte ds l'objet user ttes les données du form d'inscription
            $user = $form->getData();                                      

            // Verification Email existant
            $search_email = $this->entityManager->getRepository(User::class)->findOneByEmail($user->getEmail());

            if (!$search_email) {
                // création du password encodé
                $password = $encoder->encodePassword($user, $user->getPassword());
                $user->setPassword($password);
                /* // dd($user); // c'est top cf page inscription le résultat transmis en POST
                $doctrine = $this->getDoctrine()->getManager(); */

                // Enregistrement de l'utilisateur nouvel inscrit
                $this->entityManager->persist($user);                           // prepare : retient la requete, demande à doctrine
                $this->entityManager->flush();                                  //exec la queries et lie les valeur (bind_param() + execute()  en php)
                
                // Envoi du mail de notification success
                $this->sendEmailSuccess($user);
                //$mail = new Mail; $mail->sendEmailSuccess($user);
                
                // Affiche une notification succes
                $notification = "Votre inscription s'est correctement effectuée. Vous pouvez dès à présent vous connecter à votre compte.";
            } else {
                // Affiche une notification error
                //$notification = "L'email que vous renseigné ".$search_email->getEmail()." existe déjà.</br>";   //mode dev
                $notification = "L'email que vous renseigné existe déjà.";
            }
        }

        //Et je passe à la vue
        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
            //'controller_name' => 'RegisterController',
        ]);
    }

    /**
     * envoi l'email de notification success
     */
    public function sendEmailSuccess(User $user) {
        $mail    = new Mail();

        $content = "Bonjour ".$user->getFirstname().",<br/><br/>";
        $content .= "Bienvenue sur la première boutique dédiée au Made in France.<br/><br/>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus convallis sapien nec velit volutpat aliquam. Nulla at sapien dui. Integer tincidunt pulvinar mi ut placerat. Duis at massa vel velit tristique iaculis at at neque. Fusce lobortis, mi sit amet placerat tristique, quam justo ornare elit, et bibendum turpis metus quis nisi. Pellentesque interdum lectus sed quam semper tincidunt. Nunc efficitur eros ut dolor convallis volutpat. Etiam placerat massa non urna dictum placerat.";
        $content .= "<br/><br/>Cordialement,<br/><br/>";
        $content .= "La boutique Française";

        $mail->send($user->getEmail(), $user->getFirstname(), 'Bienvenue sur La boutique Française', $content);
    }
}
