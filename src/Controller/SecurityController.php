<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // SI L'UTILISATEUR EST DÉJÀ CONNECTÉ
        if ($this->getUser()) {
            // Si c'est un Producteur -> On le renvoie chez lui
            if (in_array('ROLE_PRODUCER', $this->getUser()->getRoles())) {
                return $this->redirectToRoute('producer_space_home');
            }
            
            // Si c'est le Staff -> On le renvoie au Dashboard
            return $this->redirectToRoute('admin');
        }

        // Sinon, on affiche le formulaire
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route('/admin/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut être vide
    }
}