<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Presenters;

use YouTubeRSS\Model\Services\YouTube;
use YouTubeRSS\Utils\Sessions;
use YouTubeRSS\Utils\Params;
use YouTubeRSS\Utils\Latte;
use YouTubeRSS\Utils\Links;
use Nette\Forms\Form;

/**
 * Presenter for the main app.
 */
class AppPresenter
{
    /**
     * Prints the error message page.
     */
    public static function showError($message)
    {
        $params = [
            'error' => $message
        ];
        Latte::render('App/error.latte', $params);
    }

    /**
     * Selects an action.
     */
    public static function run()
    {
        // log in
        if (Params::$section === 'login') {
            self::logIn();
            die(header('Location: '.Links::generateFull()));
        }

        // log out
        if (Params::$section === 'logout') {
            self::logOut();
            die(header('Location: '.Links::generateFull()));
        }

        // sign up
        if (Params::$section === 'signup') {
            if (Sessions::$user->isLogged()) {
                die(header('Location: '.Links::generateFull()));
            }
            self::signUp();
            return;
        }

        // connect
        if (Params::$section === 'connect') {
            if (Sessions::$user->isLogged() === false) {
                die(header('Location: '.Links::generateFull()));
            }
            self::connect();
            return;
        }
        
        // connect redirect
        if (isset(Params::$code)) {
            if (Sessions::$user->isLogged() === false) {
                die(header('Location: '.Links::generateFull()));
            }
            self::connectRedirect();
            die(header('Location: '.Links::generateFull()));
        }

        // main page
        if (empty(Params::$section)) {
            self::mainPage();
            return;
        }

        die(header('Location: '.Links::generateFull()));
    }

    /**
     * Page: Main page.
     */
    public static function mainPage() 
    {
        Latte::render('App/main.latte');
    }

    /**
     * Action: Log in.
     */
    public static function logIn() 
    {
        if (Sessions::$user->isLogged() === false) {
            Sessions::$user->login();
        }
    }

    /**
     * Action: Log out.
     */
    public static function logOut() 
    {
        Sessions::$user->logout();
    }

    /**
     * Page: Sign up.
     */
    public static function signUp() 
    {
        $form = new Form;
        $form->addText('username', 'Username:')
            ->setRequired('Please fill your username.')
            ->addRule(Form::LENGTH, 'Your username must be between %d to %d characters.', [3, 50]);
        $form->addPassword('password', 'Password:')
            ->setRequired('Please pick a password.')
            ->addRule(Form::MIN_LENGTH, 'Your password has to be at least %d long.', 3);
        $form->addPassword('passwordVerify', 'Password again:')
            ->setRequired('Please fill your password again to check for typos.')
            ->addRule(Form::EQUAL, 'Password mismatch.', $form['password']);
        $form->addSubmit('send', 'Sign up');

        $form->onSuccess[] = function (Form $form, \stdClass $values) {

            $result = Sessions::$user->signUp($values['username'], $values['password']);

            if ($result === true) {
                die(header('Location: '.Links::generateFull()));
            } else {
                $form->addError('This username is already taken. Please pick a different one.');
            }

        };

        if ($form->isSubmitted()) {
            $form->fireEvents();
        }

        $params = [
            'form' => $form
        ];
        Latte::render('App/signup.latte', $params);
    }

    /**
     * Action: Connect.
     */
    public static function connect()
    {
        $url = YouTube::getAuthUrl();
        die(header('Location: '.$url));
    }
    
    /**
     * Action: Connect redirect.
     */
    public static function connectRedirect()
    {
        $accessToken = YouTube::getAccessToken(Params::$code);
        $refreshToken = YouTube::getRefreshToken();
        Sessions::$user->saveTokens($accessToken, $refreshToken);
    }

}
