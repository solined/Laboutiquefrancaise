<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Address;
use App\Form\AddressType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountAddressController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/compte/addresses", name="account_address")
     */
    public function index(): Response
    {
        //dd($this->getUser()->getAddresses());
        return $this->render('account/address.html.twig');
    }

    /**
     * @Route("/compte/ajouter-une-adresse", name="account_address_add")
     * Modifier pour l'action du bouton 'valider mon panier' (commande) : ajout de $cart + condition redirection vers order
     */
    public function add(Cart $cart, Request $request): Response
    {
        $address    = new Address();
        $form       = $this->createForm(AddressType::class, $address);
        
        $form->handleRequest($request);                 //écoute la requête (en POST)
        
        // Récupère les données du formulaire et enregistre en bdd
        if ($form->isSubmitted() && $form->isValid()) {
            //lié le user
            $address->setUser($this->getUser());
            $this->entityManager->persist($address);    //prépare pour être insérer en bdd
            $this->entityManager->flush();              //Enregistre en bdd

            if ($cart->get()) {
                return $this->redirectToRoute('order');
            } else {
                return $this->redirectToRoute('account_address');
            }
        }
        //AF :  si ça rentre pas ds la condition : exception!!!
        
        // Créer le formulaire ds une var form
        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/compte/modifier-une-adresse/{id}", name="account_address_edit")
     */
    public function edit(Request $request, $id)
    {
        $address = $this->entityManager->getRepository(Address::class)->findOneById($id);

        //si elle n'existe pas (adresse)
        //si elle appartient bien à mon user //securite de ne pas taper n'importequel id ds l'url
        if (!$address || $address->getUser() != $this->getUser()) {
            return $this->redirectToRoute('account_address');
        }

        $form = $this->createForm(AddressType::class, $address);

        $form->handleRequest($request);

        // on récupère les données du formulaire et enregistre en bdd
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('account_address');
            //dd($address);
        }
        
        //on creer le formulaire ds une var form
        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/compte/supprimer-une-adresse/{id}", name="account_address_delete")
     */
    public function delete($id)
    {
        $address = $this->entityManager->getRepository(Address::class)->findOneById($id);

        //si elle n'existe pas (adresse)
        //si elle appartient bien à mon user //securite de ne pas taper n'importequel id ds l'url
        if ($address && $address->getUser() == $this->getUser()) {
            $this->entityManager->remove($address);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('account_address');

    }
}
