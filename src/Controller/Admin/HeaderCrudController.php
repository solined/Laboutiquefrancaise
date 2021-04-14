<?php

namespace App\Controller\Admin;

use App\Entity\Header;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HeaderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Header::class;
    }

    /**
     * Ajoute les champs que l'on souhaite modifier 
     * lors de la création du header
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            //IdField::new('id'),
            TextField::new('title_header', 'Titre du header'),
            TextareaField::new('content_header', 'Contenu de notre header'),
            //TextEditorField::new('description'),
            TextField::new('btnTitle_header', 'Titre de notre bouton'),
            TextField::new('btnUrl_header', 'Url de destination de notre bouton'),
            ImageField::new('illustration_header')
                ->setBasePath('uploads/')
                ->setUploadDir('public\\uploads')
                ->setUploadedFileNamePattern('[randomhash].[extension]') //evite d'enregistrer en base le nom de l'image. (ex: bonnet1.jpg sera renommer en 3ab6de4d3552c889103823181cfcbde308f922c4.jpg)
                ->setRequired(false),
        ];
    }
}
