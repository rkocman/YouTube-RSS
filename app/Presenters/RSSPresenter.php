<?php

/**
 * YT RSS
 * Author: Radim Kocman
 */

namespace YTRSS\Presenters;

use YTRSS\Utils\Sessions;
use YTRSS\Utils\Links;
use YTRSS\Utils\Latte;
use YTRSS\Model\Services\Db;
use YTRSS\Model\Services\YouTube;
use YTRSS\Model\Services\Cache;
use YTRSS\AppConfig;

/**
 * Presenter for RSS functions.
 */
class RSSPresenter
{
    
    /**
     * Page: RSS.
     */
    public static function run()
    {
        // login
        if (Sessions::$user->isLogged() === false) {
            Sessions::$user->login();
        }

        // connected
        if (Sessions::$user->isConnected() === false) {
            die(header('Location: '.Links::generateFull()));
        }

        // increase the use counter
        Db::updateUseCounter(Sessions::$user->username);

        // get data
        $data = null;
        if (AppConfig::cache) {
            $data = Cache::loadResults();
        }
        if ($data === null) {
            $data = YouTube::getNewSubscriptionVideos();
            if (AppConfig::cache) {
                Cache::saveResults($data);
            }
        }

        // debug results
        if (AppConfig::debugResults) {
            dump($data);
            exit;
        }

        // RSS
        $params = [
            'videos' => $data
        ];
        Latte::render('RSS/rss.latte', $params);
    }

}
