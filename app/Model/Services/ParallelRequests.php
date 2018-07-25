<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Model\Services;

use YouTubeRSS\Utils\Sessions;
use YouTubeRSS\AppConfig;
use Nette\Utils\Json;

/**
 * This class handles parallel requests on the YouTube API.
 */
class ParallelRequests
{
    /** @var \cURL\RequestsQueue */
    private static $queue;
    private static $requests;

    /** @var array */
    private static $results;

    /**
     * Inits the requests queue.
     */
    public static function init()
    {
        $accessToken = Json::decode(Sessions::$user->accessToken, Json::FORCE_ARRAY);
        $header = [
            'Authorization: Bearer '.$accessToken['access_token']
        ];
        self::$queue = new \cURL\RequestsQueue;
        self::$queue->getDefaultOptions()
            ->set(CURLOPT_HTTPHEADER, $header)
            ->set(CURLOPT_RETURNTRANSFER, true);
        self::$queue->addListener('complete', function (\cURL\Event $event) {
            $response = $event->response;
            $content = $response->getContent();
            $result = Json::decode($content, Json::FORCE_ARRAY);
            if (isset($result['error'])) {
                throw new \Exception('Invalid request response.');
            }
            ParallelRequests::$results[] = $result;

            if ($next = array_pop(ParallelRequests::$requests)) {
                $event->queue->attach($next);
            }
        });

        if (AppConfig::verifySslPeer === false) {
            self::$queue->getDefaultOptions()
                ->set(CURLOPT_SSL_VERIFYPEER, false);
        }
        
        self::$requests = [];
        self::$results = [];
    }

    /**
     * Adds a request into the queue.
     */
    public static function addRequest($url, $data)
    {
        $request = new \cURL\Request($url.'?'.http_build_query($data));
        self::$requests[] = $request;
    }

    /**
     * Execute requests in the queue.
     * @return array
     */
    public static function executeRequests()
    {
        for ($i = 0; $i < AppConfig::parallelRequests && count(self::$requests) > 0; $i++) {
            self::$queue->attach(array_pop(self::$requests));
        }
        self::$queue->send();
        $results = self::$results;
        self::$results = [];
        return $results;
    }

}
