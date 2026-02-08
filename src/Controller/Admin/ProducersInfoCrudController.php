<?php

namespace App\Controller\Admin;

use App\Entity\ProducersInfo;
use App\Entity\Archives;
use App\Entity\Users;
use App\Entity\Contracts;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ProducersInfoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProducersInfo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des Producteurs')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détails du Producteur')
            ->setEntityLabelInSingular('Producteur');
    }

    public function configureActions(Actions $actions): Actions
    {
        // La secrétaire (ROLE_SECRETARY) ne doit pas pouvoir supprimer
        // Seuls PDG, Director et Admin peuvent supprimer
        if ($this->isGranted('ROLE_SECRETARY') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTOR') && !$this->isGranted('ROLE_PDG')) {
            return $actions
                ->disable(Action::DELETE);
        }

        return $actions;
    }

    /**
     * C'est ICI que la magie RGPD opère.
     * Cette méthode est appelée automatiquement par EasyAdmin quand on clique sur "Supprimer".
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProducersInfo) {
            return;
        }

        // 1. CRÉATION DE L'ARCHIVE
        // On sauvegarde les données avant qu'elles ne soient détruites
        $archive = new Archives();
        $archive->setUserId($entityInstance->getUserId()); // On garde l'ID comme référence historique
        $archive->setArchiveDate(new \DateTime());
        
        // On compile les infos textuelles dans le champ "reason" pour garder une trace lisible
        $trace = sprintf(
            "ARCHIVAGE RGPD | Nom: %s | Email: %s | Siret: %s | Activité: %s | Date inscription: %s",
            $entityInstance->getContactName(),
            $entityInstance->getEmail(),
            $entityInstance->getSiret(),
            substr($entityInstance->getActivity(), 0, 50) . '...', // On coupe si c'est trop long
            $entityInstance->getRegistrationDate()->format('d/m/Y')
        );
        $archive->setReason($trace);

        $entityManager->persist($archive);

        // 2. NETTOYAGE DES DONNÉES LIÉES (User et Contrats)
        // Si le producteur a un compte utilisateur, on le supprime aussi
        $userId = $entityInstance->getUserId();
        if ($userId) {
            // A. Supprimer le User (Compte de connexion)
            $user = $entityManager->getRepository(Users::class)->find($userId);
            if ($user) {
                $entityManager->remove($user);
            }

            // B. Supprimer les Contrats associés (pour éviter les données orphelines)
            $contracts = $entityManager->getRepository(Contracts::class)->findBy(['user_id' => $userId]);
            foreach ($contracts as $contract) {
                $entityManager->remove($contract);
            }
        }

        // 3. SUPPRESSION FINALE DU PRODUCTEUR
        $entityManager->remove($entityInstance);
        
        // 4. Exécution de toutes les requêtes
        $entityManager->flush();
    }
}