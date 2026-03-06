<?php

namespace App\Controller;

use App\Entity\ProducersInfo;
use App\Entity\Contracts; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\ProducerProfileType; // <--- Importez le formulaire
use Symfony\Component\HttpFoundation\Request; // <--- Importez Request
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espace-producteur')]
#[IsGranted('ROLE_PRODUCER')] // Sécurité : Seuls les producteurs accèdent ici
class ProducerSpaceController extends AbstractController
{
    #[Route('/', name: 'producer_space_home')]
    public function index(): Response
    {
        return $this->render('producer_space/index.html.twig');
    }

    #[Route('/profil', name: 'producer_space_profile')]
    public function profile(EntityManagerInterface $em, Request $request): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();
        
        $producerInfo = $em->getRepository(ProducersInfo::class)->findOneBy(['user_id' => $user->getId()]);

        if (!$producerInfo) {
            throw $this->createNotFoundException('Aucune fiche producteur trouvée.');
        }

        // Création du formulaire
        $form = $this->createForm(ProducerProfileType::class, $producerInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush(); // Sauvegarde les modifications
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');
            
            // Redirection pour éviter la resoumission du formulaire
            return $this->redirectToRoute('producer_space_profile');
        }

        return $this->render('producer_space/profile.html.twig', [
            'producer' => $producerInfo,
            'form' => $form->createView() // On passe le formulaire à la vue
        ]);
    }

    #[Route('/contrats', name: 'producer_space_contracts')]
        
        public function contracts(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        // On récupère les contrats liés à l'ID de l'utilisateur
        $contracts = $em->getRepository(Contracts::class)->findBy(['user_id' => $user->getId()]);

        return $this->render('producer_space/contracts.html.twig', [
            'contracts' => $contracts
        ]);
    }

    // --- Demande de nouveau contrat ---
    #[Route('/nouveau-contrat', name: 'producer_request_new_contract')]
    public function requestNewContract(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        // 1. SÉCURITÉ : Vérifier si le producteur a déjà un contrat actif ou en attente
       $existingContracts = $em->getRepository(Contracts::class)->findBy(['user_id' => $user->getId()]);
        foreach ($existingContracts as $c) {
            if ($c->getStatus() === 'En attente de validation') {
                $this->addFlash('warning', 'Patience : Vous avez déjà une demande de contrat en cours d\'étude par la direction.');
                return $this->redirectToRoute('producer_space_contracts');
            }
        }

        // 2. Création du nouveau contrat "brouillon"
        $contract = new Contracts();
        $contract->setUserId($user->getId());
        $contract->setSignatureDate(new \DateTime()); // Date de la demande
        $contract->setNoticeMonths(6); // Toujours 6 mois selon le cahier des charges
        $contract->setStatus('En attente de validation');

        $em->persist($contract);
        $em->flush();

        $this->addFlash('success', 'Votre demande de nouveau contrat a bien été transmise à la direction.');

        return $this->redirectToRoute('producer_space_contracts');
    }

    // --- Demande de resiliation ---
    #[Route('/contrat/{id}/demande-resiliation', name: 'producer_request_termination')]
    public function requestTermination(int $id, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        // 1. Récupérer le contrat
        $contract = $em->getRepository(Contracts::class)->find($id);

        // 2. Sécurité : Vérifier que le contrat existe et appartient bien à l'utilisateur connecté
        if (!$contract || $contract->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce contrat.');
        }

        // 3. Vérifier si une demande n'est pas déjà en cours
        if ($contract->getStatus() === 'Demande de résiliation') {
            $this->addFlash('warning', 'Une demande est déjà en cours pour ce contrat.');
            return $this->redirectToRoute('producer_space_contracts');
        }

        // 4. Appliquer la logique métier (Préavis 2 mois)
        // On change le statut pour prévenir l'administration
        $contract->setStatus('Demande de résiliation');
        
        // Optionnel : On peut pré-remplir la date de fin, ou laisser l'admin le valider.
        // Ici, on le fait pour l'information visuelle
        $endDate = new \DateTime();
        $endDate->modify('+2 months');
        $contract->setExpirationDate($endDate);

        $em->flush();

        // 5. Message de confirmation
        $this->addFlash('success', 'Votre demande a été prise en compte. Votre contrat prendra fin le ' . $endDate->format('d/m/Y') . ' (Préavis de 2 mois).');

        return $this->redirectToRoute('producer_space_contracts');
    }
 }