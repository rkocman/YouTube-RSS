<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Model\Services;

use Google_Client;
use YouTubeRSS\YouTubeConfig;
use YouTubeRSS\Utils\Sessions;
use YouTubeRSS\Utils\Links;
use YouTubeRSS\AppConfig;
use Nette\Utils\Json;
use Nette\Utils\DateTime;
use GuzzleHttp\Client;

/**
 * YouTube model.
 */
class YouTube
{
    /** @var \Google_Client */
    private static $client;

    /** Base URL for the YouTube API. */
    private static $baseUrl = 'https://www.googleapis.com/youtube/v3/';

    /**
     * Inits the API objects.
     */
    public static function init()
    {
        self::$client = new Google_Client;
        self::$client->setClientId(YouTubeConfig::appId);
        self::$client->setClientSecret(YouTubeConfig::secret);
        self::$client->setScopes('https://www.googleapis.com/auth/youtube');
        self::$client->setRedirectUri(Links::generateFull());
        self::$client->setAccessType('offline');
        self::$client->setApprovalPrompt('force');

        if (AppConfig::verifySslPeer === false) {
            $options = ['exceptions' => false];
            $options['base_uri'] = self::$client->getConfig('base_path');
            $options['verify'] = false;
            self::$client->setHttpClient(new Client($options));
        }

        if (Sessions::$user->isConnected()) {
            self::$client->setAccessToken(Sessions::$user->accessToken);
            if (self::$client->isAccessTokenExpired()) {
                self::$client->refreshToken(Sessions::$user->refreshToken);
                $accessToken = Json::encode(self::$client->getAccessToken());
                Sessions::$user->saveTokens($accessToken, Sessions::$user->refreshToken);
            }
            ParallelRequests::init();
        }
        
    }

    /**
     * Returns the authentication url.
     * @return string
     */
    public static function getAuthUrl()
    {
        return self::$client->createAuthUrl();
    }

    /**
     * Gets an access token from the code.
     * @param string code
     * @return string
     */
    public static function getAccessToken($code)
    {
        self::$client->authenticate($code);
        return Json::encode(self::$client->getAccessToken());
    }
    
    /**
     * Gets a refresh token. (Works only after getAccessToken.)
     * @return string
     */
    public static function getRefreshToken()
    {
        return self::$client->getRefreshToken();
    }


    /**
     * Gets New Subscription Videos.
     * @return array
     */
    public static function getNewSubscriptionVideos()
    {
        ini_set('max_execution_time', AppConfig::phpTimeLimit);

        // get the videos
        $channels = self::getSubscribedChannels();
        $playlists = self::getChannelsUploadsPlaylists($channels);
        $videos = self::getPlaylistsVideos($playlists);

        // sort the videos by their published time
        usort($videos, function($a, $b) {
            return $a['publishedAt']<$b['publishedAt'];
        });

        // keep the first [maxResults] videos
        $videos = array_slice($videos, 0, AppConfig::maxResults);

        // fill details about the videos
        self::fillVideosDetails($videos);

        return $videos;
    }

    private static function getSubscribedChannels()
    {
        $channels = [];
        $next = '';
        while (1) {
            $url = self::$baseUrl.'subscriptions';
            $data = [
                'part' => 'snippet',
                'mine' => 'true',
                'maxResults' => 50,
                'fields' => 'items/snippet/resourceId/channelId,items/kind,nextPageToken',
                'pageToken' => $next
            ];
            ParallelRequests::addRequest($url, $data);
            $response = ParallelRequests::executeRequests();

            foreach ($response[0]['items'] as $item) {
                if ($item['kind'] === 'youtube#subscription') {
                    $channels[] = $item['snippet']['resourceId']['channelId'];
                }
            }

            if (isset($response[0]['nextPageToken'])) {
                $next = $response[0]['nextPageToken'];
            } else {
                break;
            }
        }
        return $channels;
    }

    private static function getChannelsUploadsPlaylists($channels)
    {
        $playlists = [];
        $chunks = array_chunk($channels, 50);
        foreach ($chunks as $chunk) {
            $url = self::$baseUrl.'channels';
            $data = [
                'part' => 'contentDetails',
                'id' => implode(',', $chunk),
                'fields' => 'items/contentDetails/relatedPlaylists/uploads',
                'maxResults' => 50
            ];
            ParallelRequests::addRequest($url, $data);
        }
        $response = ParallelRequests::executeRequests();

        foreach ($response as $request) {
            foreach ($request['items'] as $item) {
                $playlists[] = $item['contentDetails']['relatedPlaylists']['uploads'];
            }
        }
        return $playlists;
    }

    private static function getPlaylistsVideos($playlists)
    {
        $videos = [];
        foreach ($playlists as $playlist) {
            $url = self::$baseUrl.'playlistItems';
            $data = [
                'part' => 'snippet,contentDetails',
                'playlistId' => $playlist,
                'fields' => 'items/snippet/channelTitle,items/snippet/description,'
                    .'items/snippet/title,items/snippet/thumbnails/medium,'
                    .'items/snippet/resourceId/videoId,'
                    .'items/contentDetails/videoPublishedAt',
                'maxResults' => AppConfig::videosPerChannel
            ];
            ParallelRequests::addRequest($url, $data);
        }
        $response = ParallelRequests::executeRequests();
        
        $videoIds = [];
        foreach ($response as $request) {
            foreach ($request['items'] as $item) {
                if (
                    (
                        $item['snippet']['title'] === 'Private video' ||
                        $item['snippet']['title'] === 'Deleted video'
                    )
                    && !isset($item['contentDetails']['videoPublishedAt'])
                ) { 
                    continue; 
                }
                if (isset($videoIds[ $item['snippet']['resourceId']['videoId'] ])) {
                    continue;
                }
                $videoIds[ $item['snippet']['resourceId']['videoId'] ] = true;
                $videos[] = [
                    'videoId' => $item['snippet']['resourceId']['videoId'],
                    'title' => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'channel' => $item['snippet']['channelTitle'],
                    'publishedAt' => DateTime::from($item['contentDetails']['videoPublishedAt']),
                    'thumbnail_url' => $item['snippet']['thumbnails']['medium']['url'],
                    'thumbnail_width' => $item['snippet']['thumbnails']['medium']['width'],
                    'thumbnail_height' => $item['snippet']['thumbnails']['medium']['height']
                ];
            }
        }
        return $videos;
    }

    private static function fillVideosDetails(&$videos)
    {
        $details = array();

        $videoIds = [];
        foreach ($videos as $video) {
            $videoIds[] = $video['videoId'];
        }
        $chunks = array_chunk($videoIds, 50);
        foreach ($chunks as $chunk) {
            $url = self::$baseUrl.'videos';
            $data = [
                'part' => 'contentDetails',
                'id' => implode(',', $chunk),
                'fields' => 'items/id,items/contentDetails/duration',
                'maxResults' => 50
            ];
            ParallelRequests::addRequest($url, $data);
        }
        $response = ParallelRequests::executeRequests();

        foreach ($response as $request) {
            foreach ($request['items'] as $item) {
                $details[ $item['id'] ] = [
                    'duration' => self::convertDurationFormat($item['contentDetails']['duration'])
                ];
            }
        }

        foreach ($videos as &$video) {
            $video['duration'] = $details[ $video['videoId'] ]['duration'];
        }
    }

    /**
     * Converts the video duration format.
     */
    private static function convertDurationFormat($duration)
    {
        if (empty($duration)) return '?';
        $start = new \DateTime('@0'); // Unix epoch
        $start->add(new \DateInterval($duration));
        if ($start->format('G') > 0) {
            return $start->format('G:i:s');
        } else {
            return $start->format('i:s');
        }
    }

}
