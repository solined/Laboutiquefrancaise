<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }
    
    public function configureFields(string $pageName): iterable
    {
        //affiche un input a afficher et en quel format? : on va chercher les class de easyadmin // select avec differente categories
        return [
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('name'),
            ImageField::new('illustration')
                ->setBasePath('uploads/')
                ->setUploadDir('public\\uploads')
                ->setUploadedFileNamePattern('[randomhash].[extension]') //evite d'enregistrer en base le nom de l'image (bonnet1.jpg sera renommer en 3ab6de4d3552c889103823181cfcbde308f922c4.jpg)
                ->setRequired(false), //?
            TextField::new('subtitle'),
            TextareaField::new('description'),
            BooleanField::new('isBest'),
            MoneyField::new('prix')->setCurrency('EUR'),
            AssociationField::new('category')
        ];
    }
}
