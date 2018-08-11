<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Presenters;

use YouTubeRSS\Utils\Sessions;
use YouTubeRSS\Utils\Links;
use YouTubeRSS\Utils\Latte;
use YouTubeRSS\Model\Services\Db;
use YouTubeRSS\Model\Services\YouTube;
use YouTubeRSS\Model\Services\Cache;
use YouTubeRSS\AppConfig;

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
