<?php

/**
 * Contains the WordpressLoginAuthenticationProvider class, part of the Symfony2 Wordpress Bundle
 *
 * @author     Miquel Rodríguez Telep / Michael Rodríguez-Torrent <mike@themikecam.com>
 * @package    Hypebeast\WordpressBundle
 * @subpackage Security\Authentication\Provider
 */

namespace Hypebeast\WordpressBundle\Security\Authentication\Provider;

use Hypebeast\WordpressBundle\Wordpress\ApiAbstraction;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\HttpFoundation\Request;

/**
 * WordpressLoginAuthenticationProvider will authenticate the user with Wordpress
 *
 * @package    Hypebeast\WordpressBundle
 * @subpackage Security\Authentication\Provider
 * @author     Miquel Rodríguez Telep / Michael Rodríguez-Torrent <mike@themikecam.com>
 */
class WordpressLoginAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * An abstraction layer for the Wordpress API
     *
     * @var ApiAbstraction
     */
    protected $api;
    
    /**
     *
     * @var string
     */
    protected $rememberMeParameter;
    
    /**
     *
     * @var Request
     */
    protected $request;

    /**
     * Constructor
     *
     * @param ApiAbstraction $api 
     * @param string $rememberMeParameter the name of the request parameter to use to determine 
     *                                    whether to remember the user
     * @param Request $request we need the request in order to check whether to use remember me
     */
    public function __construct(ApiAbstraction $api, $rememberMeParameter = '_remember_me',
            Request $request = null)
    {
        $this->api = $api;
        $this->rememberMeParameter = $rememberMeParameter;
        $this->request = $request;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->api->wp_signon(array(
                'user_login' => $token->getUsername(),
                'user_password' => $token->getCredentials(),
                'remember' => $this->isRememberMeRequested()
        ));
        
        if ($user instanceof \WP_User) {
            $authenticatedToken = new UsernamePasswordToken(
                    $user->user_login, $token->getCredentials(), $token->getProviderKey(),
                    $user->roles);
            
            return $authenticatedToken;
            
        } else if ($user instanceof \WP_Error) {
            throw new AuthenticationException(implode(', ', $user->get_error_messages()));
        }

        throw new AuthenticationServiceException('The Wordpress API returned an invalid response');
    }
    
    /**
     * Checks whether the user requested to be remembered
     *
     * @return boolean
     */
    protected function isRememberMeRequested()
    {
        if (!$this->request) {
            return false;
        }

        $remember = $this->request->request->get($this->rememberMeParameter, null, true);

        return $remember === 'true' || $remember === 'on' || $remember === '1' || $remember === 'yes';
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof UsernamePasswordToken;
    }
}