<?php

namespace App\Controller;

use App\Entity\ProducersInfo;
use App\Entity\Contracts;
use App\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\ProducerProfileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espace-producteur')]
#[IsGranted('ROLE_PRODUCER')]
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

        $form = $this->createForm(ProducerProfileType::class, $producerInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $log = new Logs();
            $log->setUserId($user->getId());
            $log->setAction("Mise à jour de ses informations de profil par le producteur.");
            $log->setActionDate(new \DateTime());
            $em->persist($log);
            $em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');

            return $this->redirectToRoute('producer_space_profile');
        }

        return $this->render('producer_space/profile.html.twig', [
            'producer' => $producerInfo,
            'form' => $form->createView()
        ]);
    }

    #[Route('/contrats', name: 'producer_space_contracts')]

    public function contracts(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $contracts = $em->getRepository(Contracts::class)->findBy(['user_id' => $user->getId()]);

        return $this->render('producer_space/contracts.html.twig', [
            'contracts' => $contracts
        ]);
    }

    #[Route('/nouveau-contrat', name: 'producer_request_new_contract')]
   // N'oublie pas d'ajouter methods: ['POST'] ici si ce n'est pas fait !
    #[Route('/nouveau-contrat', name: 'producer_request_new_contract', methods: ['POST'])]
    public function requestNewContract(Request $request, EntityManagerInterface $em): Response // <-- La correction est ici (Request $request)
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $existingContracts = $em->getRepository(Contracts::class)->findBy(['user_id' => $user->getId()]);
        foreach ($existingContracts as $c) {
            if ($c->getStatus() === 'En attente de validation') {
                $this->addFlash('warning', 'Patience : Vous avez déjà une demande de contrat en cours d\'étude par la direction.');
                return $this->redirectToRoute('producer_space_contracts');
            }
        }

        // On récupère le texte du formulaire (Maintenant que $request est défini, ça marche !)
        $newActivity = $request->request->get('new_activity');

        $contract = new Contracts();
        $contract->setUserId($user->getId());
        $contract->setSignatureDate(new \DateTime());
        $contract->setNoticeMonths(6);
        $contract->setStatus('En attente de validation');
        $contract->setActivityDescription($newActivity);

        // <-- La simplification est ici (new Logs() au lieu de new \App\Entity\Logs())
        $log = new Logs(); 
        $log->setUserId($user->getId());
        $log->setAction("Demande d'un contrat supplémentaire par le producteur.");
        $log->setActionDate(new \DateTime());
        $em->persist($log);

        $em->persist($contract);
        $em->flush();

        $this->addFlash('success', 'Votre demande de nouveau contrat a bien été transmise à la direction.');

        return $this->redirectToRoute('producer_space_contracts');
    }

    #[Route('/contrat/{id}/demande-resiliation', name: 'producer_request_termination')]
    public function requestTermination(int $id, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $contract = $em->getRepository(Contracts::class)->find($id);

        if (!$contract || $contract->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce contrat.');
        }

        if ($contract->getStatus() === 'Demande de résiliation') {
            $this->addFlash('warning', 'Une demande est déjà en cours pour ce contrat.');
            return $this->redirectToRoute('producer_space_contracts');
        }

        $contract->setStatus('Demande de résiliation');
        $endDate = new \DateTime();
        $endDate->modify('+2 months');
        $contract->setExpirationDate($endDate);

        $log = new Logs();
        $log->setUserId($user->getId());
        $log->setAction("Demande de résiliation du contrat #" . $contract->getId() . " initiée par le producteur.");
        $log->setActionDate(new \DateTime());
        $em->persist($log);

        
        $em->flush();

        $this->addFlash('success', 'Votre demande a été prise en compte. Votre contrat prendra fin le ' . $endDate->format('d/m/Y') . ' (Préavis de 2 mois).');

        return $this->redirectToRoute('producer_space_contracts');
    }
}
