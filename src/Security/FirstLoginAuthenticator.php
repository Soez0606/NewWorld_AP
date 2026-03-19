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

        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Utilisateur non trouvé.');
        }

        if (!$user->getIsActive()) {
            throw new CustomUserMessageAuthenticationException('Compte désactivé.');
        }

        if (!$user->getHasPassword()) {
            return new SelfValidatingPassport(new UserBadge($email));
        }

        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Mot de passe requis.');
        }

        return new SelfValidatingPassport(new UserBadge($email));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Users $user */
        $user = $token->getUser();

        if (!$user->getHasPassword()) {
            return new RedirectResponse($this->urlGenerator->generate('app_set_password'));
        }

        if (in_array('ROLE_PRODUCER', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('producer_space_home'));
        }

        return new RedirectResponse($this->urlGenerator->generate('admin'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('_security.last_error', $exception);
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
