<?php

namespace App\Controller\Admin;

use App\Entity\ProducersInfo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

class ProducersInfoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProducersInfo::class;
    }
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('contact_name', 'Nom du contact'),
            EmailField::new('email', 'Email'),
            TextField::new('address', 'Adresse'),
            TextField::new('phone', 'Téléphone'),
            TextField::new('siret', 'Numéro SIRET'),
            TextField::new('activity', 'Activité'),
        ];
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions
        ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
            return $action->setCssClass('d-none');
        });
    }
}
