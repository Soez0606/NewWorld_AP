<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SetPasswordController extends AbstractController
{
    #[Route('/admin/set-password', name: 'app_set_password')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        /** @var Users $user */
        $user = $this->getUser();
        
        // Si l'utilisateur a déjà un mot de passe, on le redirige aussi selon son rôle
        if ($user->getHasPassword()) {
            return $this->redirectBasedOnRole($user);
        }
        
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit faire au moins 6 caractères.');
            } else {
                // Hasher et enregistrer le mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setHasPassword(true);
                
                $em->flush();
                
                $this->addFlash('success', 'Mot de passe défini avec succès !');
                
                // --- CORRECTION ICI : Redirection intelligente ---
                return $this->redirectBasedOnRole($user);
            }
        }
        
        return $this->render('set_password/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Petite fonction privée pour gérer la logique de redirection au même endroit
     */
    private function redirectBasedOnRole(Users $user): Response
    {
        if (in_array('ROLE_PRODUCER', $user->getRoles())) {
            // Si c'est un producteur, on l'envoie sur son espace
            return $this->redirectToRoute('producer_space_home');
        }

        // Sinon (Admin, PDG, etc.), on l'envoie sur le Dashboard
        return $this->redirectToRoute('admin');
    }
}