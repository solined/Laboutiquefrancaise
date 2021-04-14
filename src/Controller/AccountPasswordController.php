<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

// c'est ici qu'on gére la soumission de mon formulaire
class AccountPasswordController extends AbstractController
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
     * @Route("/compte/Modifier-mon-password", name="account_password")
     * @author soline durand
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $notification = null;
        
        $user = $this->getUser();
        /* assistant/helper createform() :
           Chaque formulaire doit connaître le nom de la classe qui contient les données sous-jacentes (par exemple App\Entity\Task).
           Habituellement, cela est juste deviné en fonction de l'objet passé au deuxième argument à createForm()(ie $task). */
        $form = $this->createForm(ChangePasswordType::class, $user);


        // es tu pret (formulaiere/handleRequest) a manipulé la requete entrante
        // Pour traiter les données du formulaire : https://symfony.com/doc/current/components/form.html#validation ttt des demandes
        $form->handleRequest($request); //soumet le formulaire && Request équivalent de $_POST = demande du client
        //dd($form); //"submitted : true

        if ($form->isSubmitted() && $form->isValid()) {
            //dd($form->getData()); // = dd($user);
            $old_password = $form->get('old_password')->getData(); //chope la Data de ce old_password
            //dd($form->get('old_password'));
            
            //modifier le mot de passe
            
            /* comparer les 2mp :  mp actuel et le mp en bdd crypté
             * https://symfony.com/doc/current/reference/configuration/security.html#encoders
             */
             if ( $encoder->isPasswordValid($user, $old_password) ){ //on verifie que c'est le bon user
                
                $resetPassword = new ResetPasswordController($this->entityManager);
                $resetPassword->setPasswordAndToken($form, $encoder, $user);
                /*  //recup le nouveau pass
                    $new_pwd = $form->get('new_password')->getData();
                    //dd($new_pwd);

                    //puis encoder le nouveau pass et l'enregistrer
                    $password = $encoder->encodePassword($user, $new_pwd);

                    //enregistrer le new pass
                    $user->setPassword($password);

                    //met a jour doctrine ma bdd
                    //$this->entityManager->persist($user);   // prepare pr la creation d'une entité  pas pr la MAJ : retient la requete, demande à doctrine
                    $this->entityManager->flush();          //exec la queries et lie les valeur (bind_param() + execute()  en php) */

                $notification = "Votre mot de passe a bien été mis à jour.";
                //die('CA MARCHE');
            } else {
                $notification = "Votre mot de passe actuel n'est pas le bon.";
            }
        }

        /* Maintenant que le formulaire a été créé, l'étape suivante consiste à le rendre.
           Au lieu de transmettre l'objet de formulaire entier au modèle, 
           utilisez la createView()méthode pour créer un autre objet avec la représentation visuelle du formulaire*/
        // c'est tout ce qu'on passe à la vue  Twig
        return $this->render('account/password.html.twig',[
            'form' => $form->createView(), //pour passser la vue a mon twig
            'notification' => $notification
        ]);
    }
}
