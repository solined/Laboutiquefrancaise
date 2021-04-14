<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ChangePasswordType;
use fsqasqqs  ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("mot-de-passe-oublié", name="reset_password")
     */
    public function index(Request $request): Response
    {
        // Ne pas arriver sur /mot-de-passe-oublié s'ils sont deja connecter à l'application
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        
        // Capturer ce qui est en train de se passer
        if ($request->get('email')) {
            // est ce que cet email existe en bdd ?  @email | null
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));
            
            // operation à effectuer
            if ($user) {
                $reset_password = $this->resetPassword($user);
                $this->sendMailResetPasswordToUser($user, $reset_password->getToken());

                $this->addFlash('notice', 'Votre allez recevoir dans quelques secondes un mail avec la procédure pour réinitialiser votre mot de passe.');
            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        } 

        return $this->render('reset_password/index.html.twig', [
            'controller_name' => 'ResetPasswordController',
        ]);
    }

    /**
     * @Route("modifier-mot-de-passe/{token}", name="update_password")
     * 
     * recuperer l'entrée associer à mon token
     * pour recupe user le createdAt 
     * et comparant ac la date du jour si je suis ds le bon timing pr changer mon mp
     * 
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * 
     * @author soline durand
     */
    public function formUpdatePassword($token, Request $request, UserPasswordEncoderInterface $encoder)
    {
        // Recuperer l'entree associer a mon token
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);
       
        // Vérifie que le token existe
        if (!$reset_password) {                                                 //dump((bool)$reset_password); // null
            return $this->redirectToRoute('reset_password');
        }

        // Vérifier que le token n'a pas expiré : comparer si le ceatedAt = now - 3h.
        $now        = new \DateTime();
        $date_token = $reset_password->getCreatedAt()->modify('+ 3hour');   //('+ 10minute');    //('+ 3hour');   //incrementable à chaque appel
        // TEST : 
        // $now = $now->modify('+ 4hour');
        dump($now);            //2021-03-26 18:03:57.016291 UTC (+00:00)
        dump($date_token);     //2021-03-26 18:10:01.0 UTC (+00:00)
        dump($reset_password->getIsUsed());

        if ($now < $date_token && $reset_password->getIsUsed() == false) {// && !$reset_password->getIsUsed()) {
             //$id_user          = $reset_password->getUser()->getId();    dump($id_user);
            $user             = $reset_password->getUser();             dump($user);
            //$user_to_modify   = $this->entityManager->getRepository(User::class)->findOneById($id_user); dump($user_to_modify);
            dump($user);
            
            // Rendre une vue ac mp et confirmez mp
            $notification = null;
            $form = $this->createForm(ChangePasswordType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                //dd($form->getData());
                $old_password = $form->get('old_password')->getData();
                dump($old_password);
                dump($user->getpassword());
                dump($user);
                // modifier le mot de passe
                if ($encoder->isPasswordValid($user, $old_password)) {

                    $this->setPasswordAndToken($form, $encoder, $user, $reset_password);
                    
                    /*  $new_pwd = $form->get('new_password')->getData();            // recup le nouveau pass
                        $password = $encoder->encodePassword($user, $new_pwd);       // puis encoder le nouveau pass et l'enregistrer
                        //d($user->getpassword());
                        $user->setPassword($password);                               // enregistrer le new pass
                        //$this->entityManager->persist($user);                        // met a jour doctrine ma bdd
                        
                        //delete le token ou : is_used
                        dump($reset_password);
                        $reset_password->setIsUsed(true);
                        $this->entityManager->flush();
                        //dd($reset_password); */

                    $notification = "Votre mot de passe a bien été mis à jour.";
                    $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.'); 
                    return $this->redirectToRoute('app_login');
                    /* return $this->render('security/login.html.twig', [   //pourquoi ca ne fonctionne pas ariable "notification" does not exist.
                        'notification' => $notification
                    ]); */
                } else {
                    $this->addFlash('notice', "Votre mot de passe actuel n'est pas le bon."); 
                    $notification = "Votre mot de passe actuel n'est pas le bon.";
                }
            }  
            
            //return $this->render('reset_password/update.html.twig');
            return $this->render('account/password.html.twig',[
                'form' => $form->createView(),      //$tab[0],//, //pour passser la vue a mon twig
                'notification' => $notification     //$tab[1]
            ]);

            /*  // encodage mp
                //recupe le new_mp de la vue
                //$user_to_modify->setPassword('$new_mp')    

                // flush mp en bdd
                //$this->entityManager->persist($reset_password);
                //$this->entityManager->flush(); */

            // redirection page connexion user
            $this->addFlash('notice', 'Votre mot de passe a été renouveller.');     //die('token modifions le mot de passe');

        } elseif ($now > $date_token || $reset_password->getIsUsed()) {
            //dump( $date_token);     //token à expiré
            $this->addFlash('notice', 'Votre demande de mot de passe a expiré. Merci de la renouveller.');    //die('token à expiré');
            return $this->redirectToRoute('reset_password');
        }
        //dd($reset_password);
        //return
    }

    public function setPasswordAndToken($form, $encoder, $user, $reset_password=null)
    {
        $new_pwd    = $form->get('new_password')->getData();            // recup le nouveau pass
        $password   = $encoder->encodePassword($user, $new_pwd);       // puis encoder le nouveau pass et l'enregistrer

        $user->setPassword($password);                               // enregistrer le new pass

        //delete le token ou : is_used
        dump($reset_password);
        if ($reset_password) {
            dump("set token is used");
             dump($reset_password);
            //$reset_password->setIsUsed(true);
            //  dd($reset_password);
        }
        $this->entityManager->flush();
        //dd($reset_password);
    }
/*     public function setTokenIsUsed($reset_password) {
       
    }
 */
    /**
     * 1 : Enregistrer en bdd la demande de mp oublié ac user,token,createdAt
     */
    public function resetPassword($user) {
       
        $reset_password = new ResetPassword();
            
        $reset_password->setUser($user);
        $reset_password->setToken(uniqid());
        $reset_password->setCreatedAt(new \DateTime());
        $reset_password->setIsUsed(false);
        
        $this->entityManager->persist($reset_password);
        $this->entityManager->flush();

        return $reset_password;
    }

    /**
     * Envoi un email à l'utilisateur avec un lien lui permettant de MAJ son mp
     * 
     * Envoi l'email de notification de réinitialisation de password
     * 
     * @param User       $user           le user
     * @param string     $token           demande de mp oublié
     * 
     * @var Mail         $mail           Mail à envoyé.
     * @var string       $subject        Le sujet du mail.
     * @var string       $message        Html : le message du contenu du Mail.
     * 
     * @return void
     * 
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 video 61.
     * @see notifyStateChangeToUserByMail() sendEmail() ds OrderCrudController
     * @todo faire un trait ?
     */
    public function sendMailResetPasswordToUser(User $user, $token)
    {
        // 2 : Envoyer un email à l'utilisateur ac un lien lui permettant de MAJ son mp
        $mail       = new Mail;
        $subject    = 'Réinitialiser votre mot de passe sur La Boutique Française.';
        $url        = $this->generateUrl( 'update_password', ['token' => $token, 'user' => $user] );

        $content[]  = "Vous avez demandé à réinitialiser votre mot de passe sur le site La Boutique Française.<br/><br/>";
        $content[]  = "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'>mettre à jour votre mot de passe</a>.";
        
        $content = $mail->createMailContent($content,  $user->getFullName());
        $mail->send($user->getEmail(), $user->getFullName(), $subject, null, $content);
    }  
}
