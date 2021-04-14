<?php

namespace App\Classe;

use Exception;
use InvalidArgumentException;
use Mailjet\Client;
use Mailjet\Resources;

/**
 * gestion d'envoi de mail
 * 
 * @version Version20210301074205 video 61. notifyAndSendStatusChange
 * @author Durand Soline <Solined.independant@php.net>
 */
class Mail 
{
    private $api_key        = '632613edeac29c851345a22e02722de9';
    private $api_key_secret = 'e036027b66f22029f74076c1e7e83e8b';

    /**
     * Envoi un email
     * à partir d'un message contenu dans un tableau ou une string
     * ou à partir d'un contenu entier de mail
     * 
     * @param string        $to_email   email destinataire
     * @param string        $to_name    nom destinataire : firstname ou fullname
     * @param string        $subject    objet mail
     * @param array|string  $message    message du contenu du mail à créer | null
     * @param array|string  $content    contenu entier du mail | null
     * 
     * @throws InvalidArgumentException si $message et $content sont null.
     * @throws Exception                before call send() if $content use createMailContent()
     * @throws Exception                count($content) >= 4 lignes
     * 
     * @return void
     * 
     * @todo $message est prioritaire, sur $content.
     * @use sendMailResetPasswordToUser() ; sendEmail() ordercrudcontroller
     */
    public function send($to_email, $to_name, $subject, $message=null, $content=null) //$user_name=null compris ou non ds array $conten : parser la 1ere ligne du tableau
    {
        $content    = $this->verifyArgsOfContent($to_name, $message, $content);
        $mj         = new Client($this->api_key, $this->api_key_secret, true, ['version' => 'v3.1']);       //$mj = new \Mailet\Client('632613edeac29c851345a22e02722de9','e036027b66f22029f74076c1e7e83e8b',true,['version' => 'v3.1']);
        $body       = $this->constructBodyMail($to_email, $to_name, $subject, $content);
        
        // dump ($body);//
        // var_dump($content);//
        // dd($content);//
        
        // Post le mail (crée l'Email)
        $response   = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
        //dd($response->getData());
    }

    /**
     * Verifie la conformité des arguments du contenu du mail
     * 
     * ,et le contenu : qu'il soit complet, et de type string
     * 
     * @param string        $to_name    nom destinataire : firstname ou fullname
     * @param array|string  $message    message du contenu du mail à créer | null
     * @param array|string  $content    contenu entier du mail | null
     * 
     * @return string $content
     */
    public function verifyArgsOfContent(string $to_name, $message=null, $content=null): string
    {
        // Verify args of content message
        if ($message == null && $content == null) {
            throw new InvalidArgumentException("Error : pas de contenu de mail disponible.\n Impossible d'envoyer un mail.", 1);
            //die("error : pas de contenu de mail disponible.<br/><br/>Impossible d'envoyer un mail.");
        } elseif ( ($message != null && $content == null) || ($message != null && $content != null) ) {
            $content = $this->createMailContent($message, $to_name);    
        } elseif ($content != null && $message == null){ //} && is_array($content)) {

            /*  //TEST :
                $content = null;
                $content[]  = "Bonjour, Vous avez demandé à réinitialiser votre mot de passe ";
                $content[]  = "sur le site La Boutique Française.<br/><br/>";
                // $content[]  = "Merci de bien vouloir cliquer sur le lien  ";
                // $content[]  = "Cordialement, suivant pour mettre à jour votre mot de passe.";
                // $content[]  ="La boutique Française";

                // $content .= "bonjour, Vous avez demandé à réinitialiser votre mot de passe sur le site La Boutique Française.<br/><br/>";
                // $content .= "Cordialement, Merci de bien vouloir cliquer sur le lien suivant pour La boutique Française  mettre à jour votre mot de passe. La boutique Française ";
                // $content = $this->createMailContent($content,  $to_name); */
            if (is_array($content)) {
                $this->VerifyContentOfMail($content);

                $content = $this->TransformMailContentInString($content);
            } elseif (is_string($content)) {
                $this->VerifyContentOfContentMail($content);
            }
        }
        return $content;
    }

     /**
     * Vérifie le contenu du mail
     * if is string
     * @param string $content    contenu entier du mail
     * @return void
     * 
     * @todo    //mettre en MAj bonjour  //structure
     */
    public function VerifyContentOfContentMail(string $content)
    {
        // Verify entête
        $str = str_split($content, strlen("Bonjour"));
        if (str_contains($str[0], "Bonjour") == false && str_contains($str[0], "bonjour") == false) {
            throw new Exception("Error : Contenu de mail entier attendu.\n Manque entête Bonjour; Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
        }
        
        // Verify end mail
        $end_mail   = "Cordialement,";
        $signature  = "La boutique Française";

        if (str_contains($content, $end_mail) == false) {
            throw new Exception("Error : Contenu de mail entier attendu.\n Manque la fin du mail  Cordialement,\n Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
        } elseif (str_contains($content, $signature) == false) {
            throw new Exception("Error : Contenu de mail entier attendu.\n Manque la signature La boutique Française \n Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
        } elseif (substr_compare($content, $signature, -(strlen($signature)), strlen($signature)) != 0){
            throw new Exception("Error : Contenu de mail entier attendu.\n la signature La boutique Française n'est pas en fin de phrase ou espace après  \n Impossible d'envoyer un mail.", 1);
        }
    }
    
    /**
     * Vérifie le contenu du mail
     * if is array
     * @param array $content    contenu entier du mail
     * @return void
     * @todo    //mettre en MAj bonjour
     */
    public function VerifyContentOfMail(array $content)
    {
        if (count($content) >= 4) {
            // Verify entête
            $str = str_split($content[0], strlen("bonjour"));
            if (str_contains($str[0], "Bonjour") == false && str_contains($str[0], "bonjour") == false) {
                throw new Exception("Error : Contenu de mail entier attendu.\n Manque entête Bonjour; Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
            }
            
            // Verify end mail
            $end_mail   = "Cordialement,";
            $signature  = "La boutique Française";
            $str_end_mail   = str_split($content[count($content)-2], strlen($end_mail));
            $str_signature  = str_split($content[count($content)-1], strlen($signature));

            if (str_contains($str_end_mail[0], $end_mail) == false) {
                throw new Exception("Error : Contenu de mail entier attendu.\n Manque la fin du mail  Cordialement,\n Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
            } elseif (str_contains($str_signature[0], $signature) == false) {
                throw new Exception("Error : Contenu de mail entier attendu.\n Manque la signature La boutique Française \n Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
            }    
        } elseif (count($content) < 4) {
            throw new Exception("Error : Contenu de mail entier attendu.\n Manque entête, message, fin, ou signature; Before call send() use createMailContent().\n Impossible d'envoyer un mail.", 1);
        }
    }

    /**
     * Crée le contenu du mail à partir d'un message
     * 
     * entrée, message, fin, signature
     * 
     * @param   array|string    $message    message du contenu du mail
     * @param   string          $user_name  firstname ou fullname destinataire
     * @return  string          $content    le contenu du Mail
     */
    public function createMailContent($message, string $user_name): string
    {
        $user_name  = $this->uppercaseForFirstCharOfwords($user_name);
        
        $content    = "Bonjour ".$user_name.",<br/><br/>";      //entete

        if (is_string($message)) {
            $content .= $message;
        } elseif (is_array($message)) {
            $content .= $this->TransformMailContentInString($message);
        }
        
        $content    .= "<br/><br/>Cordialement,<br/><br/>";     //end mail
        $content    .= "La boutique Française";                 //signature
       
        return $content;
    }

    /**
     * met en majuscule les 1ere lettre de chaque mots de la phrase
     * 
     * met en minuscule toutes les autre lettre de la chaine
     * 
     * @param string $user_name     chaine de caractère
     * @return string $str          
     */
    public function uppercaseForFirstCharOfwords(string $user_name): string
    {
        //if (ctype_upper($user_name) == false) {
            $str_tab = str_split($user_name);

            $str = "";
            foreach ($str_tab as $key => $char) {
                if ($key === 0 || $str_tab[$key-1] === " ") {
                    $str .= strtoupper($char);
                } elseif ($key > 0 && $str_tab[$key-1] !== " ") {
                    $str .= strtolower($char);
                }
            }

            return $str;
        //}
    }

    /**
     * Transforme le contenu du mail en string (html)
     * ou renvoie la string.
     * 
     * @param   array|string    $content    contenu à transformé ou non
     * 
     * @return  string          $content    contenu transformé
     */
    public function TransformMailContentInString($content): string
    {
        if (is_array($content)) {
            $text = "";

            foreach ($content as $key => $value) {
                $text .= $value;    //."<br/><br/>";
            }
            $content = $text;
        }
        
        return $content;
    }

    /**
     * Construit le corps du mail
     * 
     * @param string        $to_email   email destinataire
     * @param string        $to_name    nom destinataire : firstname ou fullname
     * @param string        $subject    objet mail
     * @param string        $content    contenu entier du mail
     * 
     * @return array        $body       le corps du mail
     */
    public function constructBodyMail(string $to_email, string $to_name, string $subject, string $content)
    {
        $body       = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "solined.independant@gmail.com",
                        'Name' => "la boutique française"
                    ],
                    'To' => [
                        [
                            'Email' => "$to_email",
                            'Name' => "$to_name"
                        ]
                    ],
                    /* 'Cc' => [
                        [
                            'Email' => "$cc",
                            'Name' => ""
                        ]
                    ],
                    'Bcc' => [
                        [
                            'Email' => "$cci",
                            'Name' => ""
                        ]
                    ], */
                    'TemplateID' => 2598349,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,   //"Your email flight plan!"
                    'Variables' => [
                        "content" => $content
                    ]
                    /* 'Subject' => "Greetings from Mailjet.",
                    'TextPart' => "My first Mailjet email", //$text
                    'HTMLPart' => > "$confirmationMail", //"<h3>Dear passenger 1, welcome to <a href='https://www.mailjet.com/'>Mailjet</a>!</h3><br />May the delivery force be with you!",
                    'CustomID' => "AppGettingStartedTest" */
                ]
            ]
        ];

        return $body;
    }
}