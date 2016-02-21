<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FormLoginAuthenticator extends AbstractFormLoginAuthenticator
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/login_check') {
            return;
        }

        $username = $request->request->get('_username');
        $request->getSession()->set(Security::LAST_USERNAME, $username);
        $password = $request->request->get('_password');

        return array(
            'username' => $username,
            'password' => $password
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['username'];

        // a silly example of failing with a custom message
        if ($username == 'rails_troll') {
            throw new CustomUserMessageAuthenticationException(
                'Get outta here rails_troll - we don\'t like you!'
            );
        }

        $userRepo = $this->container
            ->get('doctrine')
            ->getManager()
            ->getRepository('AppBundle:User');

        return $userRepo->findByUsernameOrEmail($username);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $plainPassword = $credentials['password'];
        $encoder = $this->container->get('security.password_encoder');
        if (!$encoder->isPasswordValid($user, $plainPassword)) {
            return false;
        }

        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // AJAX! Maybe return some JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                // you could translate the message
                array('message' => $exception->getMessageKey()),
                403
            );
        }

        // for non-AJAX requests, return the normal redirect
        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl()
    {
        return $this->container->get('router')
            ->generate('security_login');
    }

    protected function getDefaultSuccessRedirectUrl()
    {
        return $this->container->get('router')
            ->generate('homepage');
    }
}
