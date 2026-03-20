<?php

namespace App\Controller\Admin;

use App\Entity\Contracts;
use App\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\HttpFoundation\Response;

class ContractsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contracts::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $terminateAction = Action::new('terminate', 'Résilier le contrat')
            ->linkToCrudAction('terminateContract')
            ->addCssClass('btn btn-danger')
            ->setIcon('fa fa-ban')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() !== 'Résilié';
            });

        $validateAction = Action::new('validateContract', 'Valider le contrat')
            ->linkToCrudAction('validateNewContract')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'En attente de validation';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $terminateAction)
            ->add(Crud::PAGE_DETAIL, $terminateAction)
            ->add(Crud::PAGE_INDEX, $validateAction)
            ->disable(Action::DELETE, Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield IntegerField::new('user_id', 'ID Utilisateur')->setDisabled();
        yield TextareaField::new('activity_description', 'Activité concernée par ce contrat')
            ->setHelp('Laissez vide si c\'est l\'activité principale du producteur.');
        yield DateField::new('signature_date', 'Date Signature');
        yield DateField::new('expiration_date', 'Date Fin');
        yield TextField::new('status', 'Statut');
    }
    public function terminateContract(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $adminUrlGenerator): Response
    {
        /** @var Contracts $contract */
        $contract = $context->getEntity()->getInstance();

        if (!$this->isGranted('ROLE_PDG') && !$this->isGranted('ROLE_DIRECTOR')) {
            $this->addFlash('danger', 'Vous n\'avez pas les droits pour résilier un contrat.');
            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $endDate = new \DateTime();
        $endDate->modify('+6 months');

        $contract->setExpirationDate($endDate);
        $contract->setStatus('Résilié');

        /** @var \App\Entity\Users $adminUser */
        $adminUser = $this->getUser();
        if ($adminUser) {
            $log = new Logs();
            $log->setUserId($adminUser->getId());
            $log->setAction("Résiliation du contrat #" . $contract->getId() . " par la direction. Fin effective (reconduction annuelle) au " . $endDate->format('d/m/Y'));
            $log->setActionDate(new \DateTime());
            $em->persist($log);
        }

        $em->flush();

        $producerUser = $em->getRepository(\App\Entity\Users::class)->find($contract->getUserId());
        $email = $producerUser ? $producerUser->getEmail() : '';
        $producerName = $producerUser ? $producerUser->getName() : 'Cher partenaire';

        // On prépare l'objet et le corps du mail
        $subject = "Notification de résiliation de votre contrat New World";
        $body = "Bonjour {$producerName},\n\n";
        $body .= "Par la présente, la direction de New World vous notifie la résiliation de votre contrat de partenariat.\n";
        $body .= "Conformément à nos engagements (préavis de 6 mois à date anniversaire), votre contrat prendra fin de manière effective le " . $endDate->format('d/m/Y') . ".\n\n";
        $body .= "Nous vous remercions pour notre collaboration.\n\nCordialement,\nLa Direction New World";

        // On crée le lien cliquable
        $mailtoLink = "mailto:{$email}?subject=" . urlencode($subject) . "&body=" . urlencode($body);

        // On affiche un message avec un bouton pour envoyer le mail directement
        $this->addFlash('success', 'Le contrat a été résilié (Fin le ' . $endDate->format('d/m/Y') . ').');

        if ($email) {
            $this->addFlash('warning', '⚠️ OBLIGATION LÉGALE : <a href="' . $mailtoLink . '" class="btn btn-sm btn-dark ms-2" target="_blank">Cliquez ici pour envoyer la notification par mail</a>');
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }



    public function validateNewContract(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $adminUrlGenerator): Response
    {
        /** @var Contracts $contract */
        $contract = $context->getEntity()->getInstance();

        if (!$this->isGranted('ROLE_PDG') && !$this->isGranted('ROLE_DIRECTOR')) {
            $this->addFlash('danger', 'Vous n\'avez pas les droits pour signer un contrat.');
            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $startDate = new \DateTime();
        $endDate = clone $startDate;
        $endDate->modify('+6 months');

        $contract->setSignatureDate($startDate);
        $contract->setExpirationDate($endDate);
        $contract->setStatus('En cours');

        /** @var \App\Entity\Users $adminUser */
        $adminUser = $this->getUser();

        if ($adminUser) {
            $log = new Logs();
            $log->setUserId($adminUser->getId());
            $log->setAction("Validation du nouveau contrat #" . $contract->getId());
            $log->setActionDate(new \DateTime());
            $em->persist($log);
        }

        $em->flush();

        $this->addFlash('success', 'Le nouveau contrat a été activé ! Il prendra fin le ' . $endDate->format('d/m/Y'));

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }
}
