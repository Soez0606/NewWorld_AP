<?php

namespace App\Controller\Admin;

use App\Entity\ProducersInfo;
use App\Entity\Archives;
use App\Entity\Users;
use App\Entity\Logs;
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

     // --- CORRECTION DE L'ERREUR UserInterface ---
     /** @var \App\Entity\Users $adminUser */
     $adminUser = $this->getUser();

     // 1. CRÉATION DE L'ARCHIVE
     $archive = new Archives();
     $archive->setUserId($entityInstance->getUserId() ?? 0); 
     $archive->setArchiveDate(new \DateTime());

     $trace = sprintf(
         "ARCHIVAGE RGPD | Nom: %s | Email: %s | Siret: %s | Activité: %s",
         $entityInstance->getContactName(),
         $entityInstance->getEmail(),
         $entityInstance->getSiret(),
         substr($entityInstance->getActivity(), 0, 50) . '...'
     );
     $archive->setReason($trace);
     $entityManager->persist($archive);

     // 2. NOUVEAU : CRÉATION DU LOG RGPD
     if ($adminUser) {
         $log = new Logs();
         $log->setUserId($adminUser->getId()); // L'erreur a disparu grâce au DocBlock !
         $log->setAction("Suppression et Archivage du producteur : " . $entityInstance->getContactName());
         $log->setActionDate(new \DateTime());
         $entityManager->persist($log);
     }

     // 3. NETTOYAGE DES DONNÉES LIÉES
     $userId = $entityInstance->getUserId();
     if ($userId) {
         $user = $entityManager->getRepository(Users::class)->find($userId);
         if ($user) $entityManager->remove($user);

         $contracts = $entityManager->getRepository(Contracts::class)->findBy(['user_id' => $userId]);
         foreach ($contracts as $contract) {
             $entityManager->remove($contract);
         }
     }

     // 4. SUPPRESSION FINALE
     $entityManager->remove($entityInstance);
     $entityManager->flush();
 }
}