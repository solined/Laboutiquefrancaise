<?php

namespace App\Controller;

use App\Classe\Search;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\Form\Extension\Core\Type\SearchType;
use App\Form\SearchType; //pourquoi ?
use Symfony\Component\HttpFoundation\Request;
//use Doctrine\ORM\Mapping\Annotation;

/**
 * il a 2 sous-classe product et search
 * correspondant aux 2 vues et formulaires : productcrudcontroller (backoffice) et Search(frontoffice)
 * Entity : Product
 * Classe : Search
 * backoffice : productCrudController
 * frontoffice : SearchType
 */
class ProductController extends AbstractController
{
    private $entityManager;

    /**
     * AccountPasswordcontroller constructor
     * @param $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to the action: createProduct(EntityManagerInterface $entityManager)
        // $entityManager = $this->getDoctrine()->getManager();
    }

    /**
     * @Route("/Nos-produits", name="products")
     */
    public function index(Request $request)/* : Response */
    {
        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to the action: createProduct(EntityManagerInterface $entityManager)
        /* $entityManager = $this->getDoctrine()->getManager();

        $products = $entityManager->getRepository(Product::class)->findAll(); */
        $products = $this->entityManager->getRepository(Product::class);
        //dd($products);

        /* 
            // $category = new Category;

            // $product = new Product();
            // $product->setName('Keyboard');
            // $product->setSlug('Keyboard');
            // $product->setPrix(1999);
            // $product->setDescription('Ergonomic and stylish!');
            // $product->setIllustration('aa');
            // $product->setSubtitle('ffff');
            // $product->setCategory($category);


            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            //$entityManager->persist($products);

            // actually executes the queries (i.e. the INSERT query)
            //$entityManager->flush();

            //cf.https://symfony.com/doc/current/doctrine.html#persisting-objects-to-the-database
            //return new Response('Saved new product with id '.$products->getId());  //ça ne fonctionne pas dutout
         */
        $search = new Search();
        //dd($search);
        $form = $this->createForm(SearchType::class, $search);
        //dd($form);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $products = $products->findWithSearch($search); //on doit déclarer les variables en debut de fonction : p ; c pas du js
        } else {
            $products= $products->findAll();
        }

        /* if ( empty($products) ) {
            
            //"pas de produit $search->categories, $search->string";
            //réaffihcer un bouton filtre
            dd($products);
        } */

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/produit/{slug}", name="product")
     */
    public function show($slug)
    {
        //$product    = $entityManager->getRepository(Product::class)->findOneBySlug($slug);
        $product    = $this->entityManager->getRepository(Product::class)->findOneBySlug($slug);
        $products   = $this->entityManager->getRepository(Product::class)->findByIsBest(1);
        
        if (!$product) {
            return $this->redirectToRoute('products');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'products' => $products
        ]);
    }
}
