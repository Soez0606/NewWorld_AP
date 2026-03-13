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
use Symfony\Component\HttpFoundation\Response;

class ContractsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contracts::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Création de l'action "Résilier"
        $terminateAction = Action::new('terminate', 'Résilier le contrat')
            ->linkToCrudAction('terminateContract')
            ->addCssClass('btn btn-danger')
            ->setIcon('fa fa-ban')
            // Afficher seulement si le contrat n'est pas déjà résilié
            ->displayIf(static function ($entity) {
                return $entity->getStatus() !== 'Résilié';
            });

        // NOUVELLE ACTION : Valider un nouveau contrat
        $validateAction = Action::new('validateContract', 'Valider le contrat')
            ->linkToCrudAction('validateNewContract')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(static function ($entity) {
                // S'affiche uniquement si le contrat est en attente
                return $entity->getStatus() === 'En attente de validation';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $terminateAction)
            ->add(Crud::PAGE_DETAIL, $terminateAction)
            ->add(Crud::PAGE_INDEX, $validateAction) // validation de la nouvelle demane de contrat
            // Désactiver la suppression et l'ajout pour garder l'intégrité
            ->disable(Action::DELETE, Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield IntegerField::new('user_id', 'ID Utilisateur')->setDisabled();
        yield DateField::new('signature_date', 'Date Signature');
        yield DateField::new('expiration_date', 'Date Fin');
        yield TextField::new('status', 'Statut');
    }

    // La fonction qui fait le travail
    public function terminateContract(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $adminUrlGenerator): Response
    {
        /** @var Contracts $contract */
        $contract = $context->getEntity()->getInstance();

        // Vérification des droits (PDG ou Directrice uniquement)
        if (!$this->isGranted('ROLE_PDG') && !$this->isGranted('ROLE_DIRECTOR')) {
            $this->addFlash('danger', 'Vous n\'avez pas les droits pour résilier un contrat.');
            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        //  1. NOUVELLE LOGIQUE : TACITE RECONDUCTION ANNUELLE 
        $today = new \DateTime();
        $endDate = $contract->getExpirationDate();

        // Si pour une raison quelconque il n'y a pas de date de fin, on met aujourd'hui + 6 mois par défaut
        if (!$endDate) {
            $endDate = clone $today;
            $endDate->modify('+6 months');
        } else {
            // On clone pour manipuler la date sans l'écraser immédiatement
            $targetDate = clone $endDate;

            // Boucle pour trouver la prochaine date d'anniversaire valide
            // On utilise une boucle au cas où le contrat aurait été "oublié" pendant plusieurs années
            while (true) {
                // On calcule la date limite (deadline) = date anniversaire - 6 mois
                $deadline = clone $targetDate;
                $deadline->modify('-6 months');

                // Si aujourd'hui on est AVANT ou LE JOUR de la deadline, on est bon !
                if ($today <= $deadline) {
                    break;
                }

                // Si on est APRÈS la deadline, on a raté le coche. 
                // TACITE RECONDUCTION : On rajoute 1 an ferme à la date anniversaire.
                $targetDate->modify('+1 year');
            }
            $endDate = $targetDate;
        }

        // On applique la date finale calculée
        $contract->setExpirationDate($endDate);
        $contract->setStatus('Résilié');

        // --- 2. (LOG RGPD) ---
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

        // --- 3. GESTION DE LA NOTIFICATION PAR MAIL ---
        $producerUser = $em->getRepository(\App\Entity\Users::class)->find($contract->getUserId());
        $email = $producerUser ? $producerUser->getEmail() : 'Email introuvable';

        $this->addFlash('success', 'Le contrat a été résilié. Fin effective fixée au ' . $endDate->format('d/m/Y') . ' (Règle de la reconduction annuelle appliquée).');
        $this->addFlash('warning', '⚠️ CAHIER DES CHARGES : Vous devez obligatoirement notifier ce préavis au producteur par mail à l\'adresse : ' . $email);

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

        // On active le contrat : Date du jour + calcul de la fin à 6 mois
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
