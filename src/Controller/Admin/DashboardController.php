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
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $pendingCount = $this->em->getRepository(ProducersInfo::class)
            ->count(['status_audit' => ProducersInfo::STATUS_PENDING]);

        $auditRequiredCount = $this->em->getRepository(ProducersInfo::class)
            ->count(['status_audit' => ProducersInfo::STATUS_AUDIT_REQUIRED]);

        $totalProducers = $this->em->getRepository(ProducersInfo::class)->count([]);
        $activeContracts = $this->em->getRepository(Contracts::class)->count([]);
        $totalUsers = $this->em->getRepository(Users::class)->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'pendingCount' => $pendingCount,
            'auditRequiredCount' => $auditRequiredCount,
            'totalProducers' => $totalProducers,
            'activeContracts' => $activeContracts,
            'totalUsers' => $totalUsers,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('New World - Administration')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        $pendingCount = $this->em->getRepository(ProducersInfo::class)
            ->count(['status_audit' => ProducersInfo::STATUS_PENDING]);
        $auditCount = $this->em->getRepository(ProducersInfo::class)
            ->count(['status_audit' => ProducersInfo::STATUS_AUDIT_REQUIRED]);

        yield MenuItem::section('Gestion Producteurs');
        yield MenuItem::linkToRoute('Demandes en attente', 'fa fa-clock', 'admin_pending_requests')
            ->setBadge($pendingCount, $pendingCount > 0 ? 'warning' : 'secondary');
        yield MenuItem::linkToRoute('Audits requis', 'fa fa-clipboard-check', 'admin_audit_required')
            ->setBadge($auditCount, $auditCount > 0 ? 'info' : 'secondary');
        yield MenuItem::linkToCrud('Tous les producteurs', 'fa fa-tractor', ProducersInfo::class);

        yield MenuItem::section('Gestion Personnel');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', Users::class);

        yield MenuItem::section('Gestion Contrats');
        yield MenuItem::linkToCrud('Contrats', 'fa fa-file-signature', Contracts::class);
        yield MenuItem::linkToCrud('Archives', 'fa fa-archive', Archives::class);

        yield MenuItem::section('Système');
        yield MenuItem::linkToCrud('Logs', 'fa fa-clipboard-list', Logs::class);

        yield MenuItem::linkToUrl('Retour au site', 'fa fa-globe', '/');
    }
}
