<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Model\Users;

use YouTubeRSS\Constants;
use YouTubeRSS\AdminConfig;
use YouTubeRSS\Utils\Sessions;

/**
 * The admin of the app.
 */
class Admin
{
    /* admin's login status */
    private $logged;

    public function __construct()
    {
        $this->logged = false;
    }

    /**
     * Is the admin logged in?
     * @return bool
     */
    public function isLogged()
    {
        return $this->logged;
    }

    /**
     * Performs a login attempt.
     */
    public function login()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])
            && Sessions::$logout === false)
        {
            if (
                $_SERVER['PHP_AUTH_USER'] === AdminConfig::username &&
                $_SERVER['PHP_AUTH_PW'] === AdminConfig::password
            ) {
                $this->logged = true;
                return;
            }
        }
        
        Sessions::resetPHPAuth();
        Header('WWW-Authenticate: Basic realm="'.Constants::title.' Admin"');
        Header('HTTP/1.0 401 Unauthorized');
        exit;
    }
    
    /**
     * Performs the logout.
     */
    public function logout()
    {
        Sessions::resetAdmin();
        Sessions::closePHPAuth();
    }

}
