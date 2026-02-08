<?php
// src/Security/FirstLoginAuthenticator.php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class FirstLoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/login';
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('Email requis.');
        }

        // Trouver l'utilisateur
        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Utilisateur non trouvé.');
        }

        if (!$user->getIsActive()) {
            throw new CustomUserMessageAuthenticationException('Compte désactivé.');
        }

        // CAS 1 : Première connexion (pas encore de mot de passe)
        if (!$user->getHasPassword()) {
            // Authentification directe - pas besoin de vérifier le mot de passe
            return new SelfValidatingPassport(new UserBadge($email));
        }

        // CAS 2 : L'utilisateur a déjà un mot de passe
        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Mot de passe requis.');
        }

        // Pour les connexions avec mot de passe, on utilise un SelfValidatingPassport
        // Symfony vérifiera le mot de passe automatiquement via le UserProvider
        return new SelfValidatingPassport(new UserBadge($email));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Users $user */
        $user = $token->getUser();

        //1. Rediriger vers la page de création de mot de passe si c'est la première connexion
        if (!$user->getHasPassword()) {
            return new RedirectResponse($this->urlGenerator->generate('app_set_password'));
        }

        // 2. Si c'est un PRODUCTEUR -> Direction Espace Producteur
        if (in_array('ROLE_PRODUCER', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('producer_space_home'));
        }

        // 3. Sinon (Admin, PDG, Secrétaire...) -> Direction Dashboard Admin
        return new RedirectResponse($this->urlGenerator->generate('admin'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Stocker l'erreur dans la session pour l'afficher dans le formulaire
        $request->getSession()->set('_security.last_error', $exception);
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Quand un utilisateur non authentifié essaie d'accéder à une page protégée
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

   
}
