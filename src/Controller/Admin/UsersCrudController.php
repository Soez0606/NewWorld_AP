<?php
// namespace App\Controller\Admin;

// use App\Entity\Users;
// use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

// class UsersCrudController extends AbstractCrudController
// {
//     public static function getEntityFqcn(): string
//     {
//         return Users::class;
//     }
// }


namespace App\Controller\Admin;

use App\Entity\Users;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UsersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Users::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion du Personnel')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un employé (Invitation)')
            ->setEntityLabelInSingular('Employé');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Le Mini Admin (ROLE_MINI_ADMIN) a seulement le droit de VOIR
        if ($this->isGranted('ROLE_MINI_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            return $actions
                ->disable(Action::NEW, Action::EDIT, Action::DELETE)
                ->add(Crud::PAGE_INDEX, Action::DETAIL);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom complet');
        yield EmailField::new('email', 'Email professionnel');

        // CORRECTION ICI : On utilise ChoiceField mappé sur 'role_id'
        yield ChoiceField::new('role_id', 'Poste / Rôle')
            ->setChoices([
                'Administrateur' => Users::ROLE_ADMIN,
                'PDG' => Users::ROLE_PDG,
                'Directrice' => Users::ROLE_DIRECTOR,
                'Secrétaire' => Users::ROLE_SECRETARY,
                'Mini Admin' => Users::ROLE_MINI_ADMIN,
                // On peut ajouter Producteur si besoin, mais ils s'inscrivent normalement seuls
                'Producteur' => Users::ROLE_PRODUCER, 
            ])
            ->renderAsBadges([
                Users::ROLE_ADMIN => 'danger',     // Rouge
                Users::ROLE_PDG => 'warning',      // Orange
                Users::ROLE_DIRECTOR => 'warning', // Orange
                Users::ROLE_SECRETARY => 'info',   // Bleu
                Users::ROLE_MINI_ADMIN => 'secondary', // Gris
                Users::ROLE_PRODUCER => 'success', // Vert
            ])
            ->setRequired(true);

        // Indicateur visuel (Lecture seule)
        yield BooleanField::new('hasPassword', 'Compte activé')
            ->renderAsSwitch(false)
            ->hideOnForm();
    }
}