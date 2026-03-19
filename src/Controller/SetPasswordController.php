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
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setHasPassword(true);

                $em->flush();

                $this->addFlash('success', 'Mot de passe défini avec succès !');

                return $this->redirectBasedOnRole($user);
            }
        }

        return $this->render('set_password/index.html.twig', [
            'user' => $user,
        ]);
    }
    private function redirectBasedOnRole(Users $user): Response
    {
        if (in_array('ROLE_PRODUCER', $user->getRoles())) {
            return $this->redirectToRoute('producer_space_home');
        }

        return $this->redirectToRoute('admin');
    }
}
