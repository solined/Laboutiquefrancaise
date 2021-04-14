<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/Mon-panier", name="cart")
     * affiche le récapitulatif de son panier
     * 
     * au lieu de index() :by soline
     */
    public function affichage(Cart $cart): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cart->getFull()/* ,
            'controller_name' => 'CartController', */
        ]);     //$this->getFullu($cart)   //$cartComplete //$cart->get()
    }

     /**
      * exemple de sous fonction
      * ou appeller la fonction de l'objet Cart
      * appellé par index()
      * affiche le récapitulatif de son panier
      */
    /* public function getFullu($cart)
    {
        $cartComplete = [];

        if ( !empty($cart->get()) ) { //if($cart->get()) { 
            foreach ($cart->get() as $id => $quantity) {
                $cartComplete[] = [
                    'product' =>$this->entityManager->getRepository(Product::class)->findOneById($id),
                    'quantity' => $quantity
                ];
            }
        } 
        
        //     else {
        //     return $this->redirectToRoute('products');
        // }

        return $cartComplete;
    } */

    /**
     * @Route("/cart/add/{id}", name="add_to_cart")
     * ajoute un produit
     * et redirige vers mon panier
     */
    public function add(Cart $cart, $id): Response
    {
        $cart->add($id);
        
        // return $this->render('cart/index.html.twig');
        return $this->redirectToRoute('cart');
    }

    /**
     * @Route("/cart/remove/", name="remove_my_cart")
     * supprimme l'ensemble de mon panier
     * et retour aux produits
     */
    public function remove(Cart $cart): Response
    {
        $cart->remove();
        
        return $this->redirectToRoute('products');  // return $this->render('cart/index.html.twig');
    }

    /**
     * @Route("/cart/delete/{id}", name="delete_to_cart")
     * supprimme un produit dans mon panier
     */
     public function delete(Cart $cart, $id): Response
    {
        // dd("delete_to_cart", $cart);
        $cart->delete($id, null);
        
        return $this->redirectToRoute('cart');
    }

    /**
     * @Route("/cart/delete/{id}/{quantity}", name="delete_quantity_one")
     * la fonction appellé (ds cart), la f° appelé (ds twig) et qui correspond à celle-ci
     * supprimme un quantité d'un produit dans mon panier
     * decrease
     */
    public function delete_quantity_one(Cart $cart, $id, $quantity): Response
    {
        $cart->delete($id, $quantity);

        return $this->redirectToRoute('cart');
    }
}
