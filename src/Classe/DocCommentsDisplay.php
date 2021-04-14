<?php

namespace App\Classe;

use App\Controller\AccountController;
use App\Controller\AccountOrderController;
use App\Controller\StripeController;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use function PHPSTORM_META\type;

class DocCommentsDisplay
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /* public function __toString()
    {
        return '';
    } */

    /**
     * @param Class $class : classe à documenter  ou nom du class
     * @todo Regler le pb $class (string en objet) new $class
     */
    public function AffichageDocComments(string $class=null)
    {
        //tester le type string ou objet ? AF
        $className = 'App\Controller\\'.$class;
        
        // Afficher les commentaires
        if ($class != null) {
           $object  = new $className($this->entityManager);//StripeController($this->entityManager); //AccountController;    //$class;
        } else {
            $object = new AccountOrderController($this->entityManager);//StripeController($this->entityManager); //AccountController;    //$class;
        }
        $function   = new ReflectionClass($object); 
        //dump($function->getDocComment());
        
        // Parcourir le nom des methode : recup
        $tab_method = [];
        foreach ($function->getMethods() as $key => $value) {

            if ( $value->getName() != "setContainer"){
                $tab_method_name[] = $value->getName();
            } else {
                break;
            }
        }
        //dd($tab_method_name);

        $tab            = [];
        foreach ($tab_method_name as $key => $value) {
            $tab[$tab_method_name[$key]] = (new ReflectionClass($object))->getMethod($value)->getdoccomment();
            //$this->DisplayComments($tab[$tab_method_name[$key]]);
        }
        dd($tab);


        //get the comment string
        $comment_string = null;
      /*   $comment_string = (new ReflectionClass($object))->getMethod('index')->getdoccomment();
        $methods    = $function->getMethods();
     dump($methods); //affiché que jusqu'à setContainer
        $comment_string .= (new ReflectionClass($object))->getMethod('show')->getdoccomment();
        $comment_string .= ($function->getName())."\n"; */
        $comment_string .= ($function->inNamespace())."\n";
        //var_dump($function->getName());
        $comment_string .= ($function->getNamespaceName())."\n";
        $comment_string .= ($function->getShortName())."\n";
        var_dump($function->getDefaultProperties())."\n";   //recupe les $propriété
        dump($comment_string);
    
       /*  $object = new $class;
        $rc     = new ReflectionClass($object);
        echo($rc->getDocComment());
        $rc->getMethod('test')->getDocComment();
        dd("pouet"); */
    }

    public function DisplayComments($comment_string)
    {
       //define the regular expression pattern to use for string matching
       $pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";
    
       //perform the regular expression on the string provided
       preg_match_all($pattern, $comment_string, $matches, PREG_PATTERN_ORDER);
   
       echo "<pre>"; print_r($matches);        //pourquoi 2 fois le meme resultat ?
    }
}