<?php

namespace AppBundle\Security;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FacebookAuthenticator extends SocialAuthenticator
{
    /**
     * @var Facebook
     */
    private $facebookClient;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(FacebookClient $facebookClient, EntityManager $em, RouterInterface $router)
    {
        $this->facebookClient = $facebookClient;
        $this->em = $em;
        $this->router = $router;
    }

    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/connect/facebook-check') {
            // skip authentication unless we're on this URL!
            return null;
        }

        try {
            return $this->facebookClient->getAccessToken($request);
        } catch (IdentityProviderException $e) {
            // you could parse the response to see the problem
            throw $e;
        }
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var AccessToken $accessToken */
        $accessToken = $credentials;

        $facebookUser = $this->facebookClient->fetchUserFromToken($accessToken);
        $email = $facebookUser->getEmail();

        // 1) have they logged in with Facebook before? Easy!
        $existingUser = $this->em->getRepository('AppBundle:User')
            ->findOneBy(array('facebookId' => $facebookUser->getId()));
        if ($existingUser) {
            return $existingUser;
        }

        // 2) do we have a matching user by email?
        $user = $this->em->getRepository('AppBundle:User')
                    ->findOneBy(array('email' => $email));

        // 3) no user? Perhaps you just want to create one
        //      or maybe you want to redirect to a registration (in that case, keep reading_
        if (!$user) {
            $user = new User();
            $user->setUsername($email);
            $user->setEmail($email);
            // set an un-encoded password, which basically makes it *not* possible
            // to login with any password
            $user->setPassword('no password');
        }

        // make sure the Facebook user is set
        $user->setFacebookId($facebookUser->getId());
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
        // do nothing - the fact that the access token worked means that
        // our app has been authorized with Facebook
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // this would happen if something went wrong in the OAuth flow
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        $url = $this->router
            ->generate('security_login');

        return new RedirectResponse($url);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // todo - remove needing this crazy thing
        $targetPath = $request->getSession()->get('_security.'.$providerKey.'.target_path');

        if (!$targetPath) {
            $router = $this->router;
            $targetPath = $router->generate('homepage');
        }

        return new RedirectResponse($targetPath);
    }

    /**
     * Called when an anonymous user tries to access an protected page.
     *
     * In our app, this is never actually called, because there is only *one*
     * "entry_point" per firewall and in security.yml, we're using
     * app.form_login_authenticator as the entry point (so it's start() method
     * is the one that's called).
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // not called in our app, but if it were, redirecting to the
        // login page makes sense
        $url = $this->router
            ->generate('security_login');

        return new RedirectResponse($url);
    }
}
