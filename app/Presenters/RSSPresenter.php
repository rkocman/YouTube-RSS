<?php

/**
 * Metro RSS
 * Author: Radim Kocman
 */

namespace MetroRSS\Presenters;

use MetroRSS\Utils\Sessions;
use MetroRSS\Utils\Links;
use MetroRSS\Utils\Latte;
use MetroRSS\Model\Services\Db;
use MetroRSS\Model\Services\YouTube;
use MetroRSS\Model\Services\Cache;
use MetroRSS\AppConfig;

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
        if (AppConfig::cacheResult) {
            $data = Cache::loadResults();
        }
        if ($data === null) {
            $data = YouTube::getNewSubscriptionVideos();
            if (AppConfig::cacheResult) {
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
