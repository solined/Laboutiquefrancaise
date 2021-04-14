<?php

namespace App\Classe;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{
    private $session;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->session = $session;
        $this->entityManager = $entityManager;
    }

    public function add($id)
    {
        $cart = $this->get();   //$this->session->get('cart', []);

        //s'il existe déjà dans mon panier, tu m'ajoute une quantité
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        return($this->set($cart));  //$this->session->set('cart', $cart);
        //a la mano
         /*  [
            'id' => $id,
            'quantity' => 1
        ] );*/
    }

    public function get()
    {
        return $this->session->get('cart');
    }

    public function set($cart)
    {
        return $this->session->set('cart', $cart);
    }


    public function remove()
    {
        return $this->session->remove('cart');
    }
    
    /**
     * @$id : id du produit
     * @quantity : sa quantité
     * s'il n'ya pas de quantité : supprime le produit complet
     * sinon : supprime une quantité du produit
     */
    public function delete($id, $quantity=null)
    {
        $cart = $this->get();   // $this->session->get( 'cart', [] );

        if ($quantity==null || $quantity == 1) {
            unset($cart[$id]);
        } else {
            $cart[$id]--;
        }
        
        return($this->set($cart));  //$this->session->set('cart', $cart);
    }


    /**
     * @product_object : != null   ;   =true
     * @id : id produit existant ds la bdd
     */
    public function getFull()
    {
        $cartComplete = [];
        // var_dump(empty($this->get())); //Retourne false si var existe et est non-vide, une valeur différent de zéro
        // var_dump($this->get());
        if ( !empty($this->get()) ) { //if($cart->get()) {
            foreach ($this->get() as $id => $quantity) {
                $product_object = $this->entityManager->getRepository(Product::class)->findOneById($id);
                
                // !=null  =true
                if (!$product_object) {
                    $this->delete($id);
                    continue;   //passe au produit suivant
                }
                
                $cartComplete[] = [
                    'product' => $product_object,
                    'quantity' => $quantity
                ];
            }
        } 
        /*  // à la mano
            else {
            return $this->redirectToRoute('products');
        } */
        return $cartComplete;
    }
}