<?php

namespace App\Controller\Admin;

use App\Entity\Contracts;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class TerminationRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contracts::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, '⚠️ Demandes de Résiliation')
            ->setEntityLabelInSingular('Demande')
            ->setDefaultSort(['expiration_date' => 'ASC'])
            ->setPaginatorPageSize(20);
    }

    // 1. LE FILTRE MAGIQUE : On ne montre que les demandes
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.status = :status')
            ->setParameter('status', 'Demande de résiliation');
    }

  public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield DateField::new('signature_date', 'Signature');
        yield DateField::new('expiration_date', 'Date Fin Prévue')
            ->setHelp('Calculée automatiquement (Préavis 2 mois)');

        // CORRECTION ICI : On utilise ChoiceField au lieu de TextField
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En cours' => 'En cours',
                'Demande de résiliation' => 'Demande de résiliation',
                'Résilié' => 'Résilié',
            ])
            ->renderAsBadges([
                'En cours' => 'success',       // Vert
                'Demande de résiliation' => 'warning', // Orange/Jaune
                'Résilié' => 'danger',         // Rouge
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        // 2. L'ACTION DE VALIDATION
        $validateAction = Action::new('validateTermination', 'Accepter la résiliation')
            ->linkToCrudAction('validateTermination')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check');

        return $actions
            ->add(Crud::PAGE_INDEX, $validateAction)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT); // On veut juste voir et valider
    }

    // 3. LA LOGIQUE MÉTIER
    public function validateTermination(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $adminUrlGenerator): Response
    {
        /** @var Contracts $contract */
        $contract = $context->getEntity()->getInstance();

        // On valide définitivement
        $contract->setStatus('Résilié');
        $em->flush();

        $this->addFlash('success', 'La résiliation a été acceptée. Le contrat est maintenant terminé.');

        // On recharge la page (la ligne disparaitra car elle n'a plus le statut "Demande...")
        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }
}