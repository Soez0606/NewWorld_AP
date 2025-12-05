<?php

namespace App\Controller\Admin;

use App\Entity\ProducersInfo;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/producer')]
class ProducerActionsController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/admin/producer', name: 'admin_producer')]
    public function index(): Response
    {
        // Redirige vers la page des demandes en attente
        return $this->redirectToRoute('admin_pending_requests');
    }

    #[Route('/admin/producer/pending-requests', name: 'admin_pending_requests')]
    public function pendingRequests(): Response
    {
        $pendingProducers = $this->em->getRepository(ProducersInfo::class)
            ->findBy(['status_audit' => ProducersInfo::STATUS_PENDING], ['registration_date' => 'DESC']);

        return $this->render('admin/pending_requests.html.twig', [
            'producers' => $pendingProducers
        ]);
    }

    #[Route('/admin/producer/audit-required', name: 'admin_audit_required')]
    public function auditRequired(): Response
    {
        $auditProducers = $this->em->getRepository(ProducersInfo::class)
            ->findBy(['status_audit' => ProducersInfo::STATUS_AUDIT_REQUIRED], ['registration_date' => 'DESC']);

        return $this->render('admin/audit_required.html.twig', [
            'producers' => $auditProducers
        ]);
    }

    #[Route('/admin/producer/{id}/validate', name: 'admin_producer_validate')]
    public function validateProducer(int $id, Request $request): Response
    {
        $producer = $this->em->getRepository(ProducersInfo::class)->find($id);

        if (!$producer) {
            $this->addFlash('error', 'Producteur non trouvé.');
            return $this->redirectToRoute('admin_pending_requests');
        }

        $producer->setStatusAudit(ProducersInfo::STATUS_AUDIT_REQUIRED);
        $this->em->flush();

        // Générer le lien mailto
        $mailtoLink = $this->generateValidationEmail($producer);

        // Stocker en session
        $request->getSession()->set('pending_mailto', $mailtoLink);

        $this->addFlash('success', 'Demande validée ! Un email d\'audit doit être envoyé.');

        return $this->redirectToRoute('admin_pending_requests');
    }


    #[Route('/{id}/reject', name: 'admin_producer_reject')]
    public function rejectProducer(int $id, Request $request): Response
    {
        $producer = $this->em->getRepository(ProducersInfo::class)->find($id);

        if (!$producer) {
            $this->addFlash('error', 'Producteur non trouvé.');
            return $this->redirectToRoute('admin_pending_requests');
        }

        $producer->setStatusAudit(ProducersInfo::STATUS_REJECTED);
        $producer->setArchived(true);
        $this->em->flush();

        $mailtoLink = $this->generateRejectionEmail($producer);
        $request->getSession()->set('pending_mailto', $mailtoLink);

        $this->addFlash('warning', 'Demande refusée ! Un email de refus doit être envoyé.');

        return $this->redirectToRoute('admin_pending_requests');
    }

    #[Route('/{id}/approve-audit', name: 'admin_producer_approve_audit')]
    public function approveAudit(int $id, Request $request): Response
    {
        $producer = $this->em->getRepository(ProducersInfo::class)->find($id);

        if (!$producer) {
            $this->addFlash('error', 'Producteur non trouvé.');
            return $this->redirectToRoute('admin_audit_required');
        }

        $producer->setStatusAudit(ProducersInfo::STATUS_APPROVED);
        $producer->setValidationAuditDate(new \DateTime());

        // Créer le compte utilisateur
        $user = new Users();
        $user->setName($producer->getContactName());
        $user->setEmail($producer->getEmail());
        $user->setRoleId(Users::ROLE_PRODUCER);

        $tempPassword = bin2hex(random_bytes(8));
        $user->setPassword(password_hash($tempPassword, PASSWORD_DEFAULT));

        $this->em->persist($user);
        $this->em->flush();

        $producer->setUserId($user->getId());
        $this->em->flush();

        $mailtoLink = $this->generateApprovalEmail($producer, $tempPassword);
        $request->getSession()->set('pending_mailto', $mailtoLink);

        $this->addFlash('success', 'Audit validé ! Compte créé. Un email avec les identifiants doit être envoyé.');

        return $this->redirectToRoute('admin_audit_required');
    }

    #[Route('/{id}/reject-audit', name: 'admin_producer_reject_audit')]
    public function rejectAudit(int $id, Request $request): Response
    {
        $producer = $this->em->getRepository(ProducersInfo::class)->find($id);

        if (!$producer) {
            $this->addFlash('error', 'Producteur non trouvé.');
            return $this->redirectToRoute('admin_audit_required');
        }

        $producer->setStatusAudit(ProducersInfo::STATUS_AUDIT_REJECTED);
        $producer->setArchived(true);
        $this->em->flush();

        $mailtoLink = $this->generateAuditRejectionEmail($producer);
        $request->getSession()->set('pending_mailto', $mailtoLink);

        $this->addFlash('warning', 'Audit refusé ! Un email de refus doit être envoyé.');

        return $this->redirectToRoute('admin_audit_required');
    }

    private function generateValidationEmail(ProducersInfo $producer): string
    {
        $subject = "🎉 Votre demande AP New World - Audit requis";
        $body = "Bonjour {$producer->getContactName()},\n\n";
        $body .= "Félicitations ! Votre demande d'adhésion a été pré-approuvée.\n\n";
        $body .= "📅 PROCHAIN ÉTAPE : AUDIT EN PRÉSENTIEL\n";
        $body .= "Un de nos inspecteurs vous contactera sous peu pour planifier un audit.\n\n";
        $body .= "Cordialement,\nÉquipe AP New World";

        return $this->generateMailtoLink($producer->getEmail(), $subject, $body);
    }

    private function generateRejectionEmail(ProducersInfo $producer): string
    {
        $subject = "Votre demande AP New World";
        $body = "Bonjour {$producer->getContactName()},\n\n";
        $body .= "Nous avons étudié votre demande avec attention.\n\n";
        $body .= "Malheureusement, nous ne pouvons pas donner suite à votre candidature pour le moment.\n\n";
        $body .= "Cordialement,\nÉquipe AP New World";

        return $this->generateMailtoLink($producer->getEmail(), $subject, $body);
    }

    private function generateApprovalEmail(ProducersInfo $producer, string $tempPassword): string
    {
        $subject = "🎉 Félicitations ! Votre adhésion AP New World est validée";
        $body = "Bonjour {$producer->getContactName()},\n\n";
        $body .= "Félicitations ! Votre audit a été validé avec succès.\n\n";
        $body .= "📝 VOTRE COMPTE A ÉTÉ CRÉÉ\n";
        $body .= "Identifiant : {$producer->getEmail()}\n";
        $body .= "Mot de passe temporaire : {$tempPassword}\n";
        $body .= "Lien de connexion : http://localhost:8000/admin/login\n\n";
        $body .= "⚠️ IMPORTANT : Changez votre mot de passe dès votre première connexion.\n\n";
        $body .= "Bienvenue dans la communauté AP New World !\n\n";
        $body .= "Cordialement,\nÉquipe AP New World";

        return $this->generateMailtoLink($producer->getEmail(), $subject, $body);
    }

    private function generateAuditRejectionEmail(ProducersInfo $producer): string
    {
        $subject = "Résultat de votre audit AP New World";
        $body = "Bonjour {$producer->getContactName()},\n\n";
        $body .= "Suite à votre audit, nous regrettons de vous informer que votre candidature n'a pas été retenue.\n\n";
        $body .= "Cordialement,\nÉquipe AP New World";

        return $this->generateMailtoLink($producer->getEmail(), $subject, $body);
    }

    private function generateMailtoLink(string $to, string $subject, string $body): string
    {
        return "mailto:{$to}?subject=" . urlencode($subject) . "&body=" . urlencode($body);
    }

    // Méthode requise par AbstractDashboardController
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🌍 AP New World - Administration')
            ->setFaviconPath('favicon.ico');
    }

    // Méthode requise par AbstractDashboardController
    public function configureMenuItems(): iterable
    {
        // Vous pouvez retourner un tableau vide ou configurer le menu
        return [];
    }
}
