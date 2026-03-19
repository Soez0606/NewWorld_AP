<?php

namespace App\Controller;

use App\Entity\ProducersInfo;
use App\Form\ProducerRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProducerController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/inscription-producteur', name: 'producer_registration')]
    public function registration(Request $request, EntityManagerInterface $em): Response
    {
        $producerInfo = new ProducersInfo();
        $form = $this->createForm(ProducerRegistrationType::class, $producerInfo);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $producerInfo->setStatusAudit(ProducersInfo::STATUS_PENDING);
            $producerInfo->setRegistrationDate(new \DateTime());
            $producerInfo->setArchived(false);
            $producerInfo->setUserId(0);

            $em->persist($producerInfo);
            $em->flush();

            $request->getSession()->set('last_producer_id', $producerInfo->getId());

            return $this->redirectToRoute('producer_registration_success');
        }

        return $this->render('producer/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/inscription/success', name: 'producer_registration_success')]
    public function registrationSuccess(Request $request, EntityManagerInterface $em): Response
    {
        $producerId = $request->getSession()->get('last_producer_id');

        if (!$producerId) {
            return $this->redirectToRoute('producer_registration');
        }

        $producer = $em->getRepository(ProducersInfo::class)->find($producerId);

        if (!$producer) {
            $this->addFlash('error', 'Demande non trouvée.');
            return $this->redirectToRoute('home');
        }

        $request->getSession()->remove('last_producer_id');

        return $this->render('producer/registration_success.html.twig', [
            'producer' => $producer
        ]);
    }
}
