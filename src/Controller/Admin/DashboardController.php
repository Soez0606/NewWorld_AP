<?php

namespace App\Controller\Admin;

use App\Entity\Users;
use App\Entity\ProducersInfo;
use App\Entity\Contracts;
use App\Entity\Archives;
use App\Entity\Logs;
use App\Entity\Roles;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use App\Controller\Admin\ContractsCrudController; // <--- AJOUTER CETTE LIGNE
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Vérification de sécurité standard
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var Users $user */
        $user = $this->getUser();

        if ($user && !$user->getHasPassword()) {
            return $this->redirectToRoute('app_set_password');
        }

        // Calcul des stats (visible par Admin, Directrice, PDG)
        // Le Mini Admin verra un tableau de bord vide ou adapté, mais pas d'erreur.
        $pendingCount = $this->em->getRepository(ProducersInfo::class)->count(['status_audit' => ProducersInfo::STATUS_PENDING]);
        $auditRequiredCount = $this->em->getRepository(ProducersInfo::class)->count(['status_audit' => ProducersInfo::STATUS_AUDIT_REQUIRED]);
        $totalProducers = $this->em->getRepository(ProducersInfo::class)->count([]);
        $activeContracts = $this->em->getRepository(Contracts::class)->count([]);
        $totalUsers = $this->em->getRepository(Users::class)->count([]);

        // AJOUT : Compteur des demandes de résiliation
        $terminationRequestsCount = $this->em->getRepository(Contracts::class)->count(['status' => 'Demande de résiliation']);

        return $this->render('admin/dashboard.html.twig', [
            'pendingCount' => $pendingCount,
            'auditRequiredCount' => $auditRequiredCount,
            'totalProducers' => $totalProducers,
            'activeContracts' => $activeContracts,
            'totalUsers' => $totalUsers,
            'terminationRequestsCount' => $terminationRequestsCount, //resiliation dde contrat demande
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🌍 AP New World')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');

        // --- GESTION PRODUCTEURS (Directrice, PDG, Secrétaire, Admin) ---
        // Le Mini Admin ne voit PAS ça.
        if ($this->isGranted('ROLE_SECRETARY') || $this->isGranted('ROLE_DIRECTOR') || $this->isGranted('ROLE_PDG') || $this->isGranted('ROLE_ADMIN')) {
            
            yield MenuItem::section('Gestion Producteurs');

            // Seuls ceux qui valident (Admin, Directrice) voient les compteurs d'alerte
            if ($this->isGranted('ROLE_DIRECTOR') || $this->isGranted('ROLE_ADMIN')) {
                $pendingCount = $this->em->getRepository(ProducersInfo::class)->count(['status_audit' => ProducersInfo::STATUS_PENDING]);
                $auditCount = $this->em->getRepository(ProducersInfo::class)->count(['status_audit' => ProducersInfo::STATUS_AUDIT_REQUIRED]);
                
                yield MenuItem::linkToRoute('Demandes en attente', 'fa fa-clock', 'admin_pending_requests')
                    ->setBadge($pendingCount, $pendingCount > 0 ? 'warning' : 'secondary');
                yield MenuItem::linkToRoute('Audits requis', 'fa fa-clipboard-check', 'admin_audit_required')
                    ->setBadge($auditCount, $auditCount > 0 ? 'info' : 'secondary');
            }

            yield MenuItem::linkToCrud('Tous les producteurs', 'fa fa-tractor', ProducersInfo::class);
        }

        // --- GESTION PERSONNEL (Admin, Mini Admin) ---
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MINI_ADMIN')) {
            yield MenuItem::section('Gestion Personnel');
            yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', Users::class);
            
            // Seul l'Admin gère les Rôles techniques
            if ($this->isGranted('ROLE_ADMIN')) {
                yield MenuItem::linkToCrud('Rôles', 'fa fa-user-tag', Roles::class);
            }
        }

        // --- GESTION CONTRATS & ARCHIVES (Directrice, PDG, Admin) ---
        // La secrétaire ne voit PAS ça.
        if ($this->isGranted('ROLE_DIRECTOR') || $this->isGranted('ROLE_PDG') || $this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::section('Contrats & Juridique');
          // Compteur pour le badge
            $terminationCount = $this->em->getRepository(Contracts::class)->count(['status' => 'Demande de résiliation']);
            
            // MODIFICATION ICI : On utilise linkToCrud vers le nouveau contrôleur
            yield MenuItem::linkToCrud('Résiliations demandées', 'fa fa-bell', Contracts::class)
                ->setController(TerminationRequestCrudController::class) // <--- On pointe vers le contrôleur spécial
                ->setBadge($terminationCount, $terminationCount > 0 ? 'danger' : 'secondary'); 
                
            //yield MenuItem::linkToCrud('Contrats', 'fa fa-file-signature', Contracts::class);
            yield MenuItem::linkToCrud('Tous les Contrats', 'fa fa-file-signature', Contracts::class)
                ->setController(ContractsCrudController::class);
            yield MenuItem::linkToCrud('Archives', 'fa fa-archive', Archives::class);
        }

        // --- SYSTÈME (Admin uniquement) ---
        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::section('Système');
            yield MenuItem::linkToCrud('Logs', 'fa fa-clipboard-list', Logs::class);
        }

        yield MenuItem::section('Navigation');
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-globe', '/');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt');
    }
}