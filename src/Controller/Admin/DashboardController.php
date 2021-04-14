<?php

namespace App\Controller\Admin;

use App\Entity\Carrier;
use App\Entity\Category;
use App\Entity\Header;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
//use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        //return parent::index();

        // -> Partie EasyAdmin copiée sur la page Dashboard
        // redirect to some CRUD controller
        $routeBuilder = $this->get(AdminUrlGenerator::class);       //(CrudUrlGenerator::class);//lui met cela mais ça ne fonctionne pas!

        return $this->redirect($routeBuilder->setController(OrderCrudController::class)->generateUrl());

        /* // you can also redirect to different pages depending on the current user
            if ('jane' === $this->getUser()->getUsername()) {
                return $this->redirect('...');
            }

            // you can also render some template to display a proper Dashboard
            // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
            return $this->render('some/path/my-dashboard.html.twig'); 
        */
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('La Boutique Française');
    }

    /**
     * @todo commande : v54 : editer bien pr SAV mais sinon vaut mieux l'enlever
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        // cf. https://fontawesome.com/icons/list?style=solid  ou https://www.w3schools.com/icons/fontawesome5_icons_travel.asp
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class); //Users
        yield MenuItem::linkToCrud('Catégories', 'fa fa-list', Category::class);
        yield MenuItem::linkToCrud('Produits', 'fas fa-tag', Product::class);
        yield MenuItem::linkToCrud('Transporteurs', 'fas fa-truck', Carrier::class); //Carriers
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Order::class);   //AF editer bien pr SAV mais sinon vaut mieux l'enlever
        yield MenuItem::linkToCrud('Header', 'fa fa-desktop', Header::class);  
    }
}
