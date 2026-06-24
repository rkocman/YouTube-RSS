<?php

/**
 * Metro RSS
 * Author: Radim Kocman
 */

namespace MetroRSS\Model\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MetroRSS\Utils\Sessions;
use MetroRSS\AppConfig;
use Nette\Utils\Json;
use Tracy\Debugger;

/**
 * This class handles parallel requests on the YouTube API.
 */
class ParallelRequests
{
    private static Client $client;
    /** @var Request[] */
    private static array $requests;
    private static array $contexts;
    private static array $results;

    /**
     * Inits the requests queue.
     */
    public static function init()
    {
        $accessToken = Json::decode(Sessions::$user->accessToken, true);
        self::$client = new Client([
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken['access_token']
            ],
            'verify' => AppConfig::verifySslPeer,
            'http_errors' => false
        ]);
        self::$requests = [];
        self::$results = [];
    }

    /**
     * Adds a request into the queue.
     */
    public static function addRequest($url, $data, $context = null)
    {
        $request = new Request('GET', $url.'?'.http_build_query($data));
        self::$requests[] = $request;
        self::$contexts[] = $context;
    }

    /**
     * Execute requests in the queue.
     * @return array
     */
    public static function executeRequests()
    {
        $pool = new Pool(self::$client, self::$requests, [
            'concurrency' => AppConfig::parallelRequests,
            'fulfilled' => function (Response $response, $index) {
                $result = Json::decode($response->getBody()->getContents(), true);
                if (isset($result['error'])) {
                    $request = self::$requests[$index]->getUri();
                    $context = self::$contexts[$index];
                    $exception = new \Exception('Invalid request response: status: '.$response->getStatusCode().'; context: '.json_encode($context).'; request: '.$request.'; response: '.$response->getBody()->getContents().'.');
                    if (isset($result['error']['errors'][0]['reason']) && $result['error']['errors'][0]['reason'] === 'playlistNotFound') {
                        Debugger::log($exception);
                    } else {
                        throw $exception;
                    }
                } else {
                    ParallelRequests::$results[] = $result;
                }
            },
            'rejected' => function (RequestException $reason, $index) {
                throw $reason;
            },
        ]);
        $pool->promise()->wait();

        $results = self::$results;
        self::$requests = [];
        self::$contexts = [];
        self::$results = [];
        return $results;
    }

}
