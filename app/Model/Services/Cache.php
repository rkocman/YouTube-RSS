<?php

/**
 * Metro RSS
 * Author: Radim Kocman
 */

namespace MetroRSS\Model\Services;

use MetroRSS\Utils\Sessions;
use MetroRSS\Utils\Path;
use MetroRSS\AppConfig;
use Nette\Caching;
use Nette\Caching\Storages\FileStorage;

/**
 * Cache for the results.
 */
class Cache 
{
    private static ?\Nette\Caching\Cache $cache = null;

    /** @return \Nette\Caching\Cache  */
    private static function getCache()
    {
        if (self::$cache === null) {
            $storage = new FileStorage(Path::getTemp());
            self::$cache = new Caching\Cache($storage);
        }
        return self::$cache;
    }

    /**
     * Saves user's results.
     */
    public static function saveResults($data)
    {
        $cache = self::getCache();
        $cache->save(Sessions::$user->id, $data, [
            Caching\Cache::Expire => AppConfig::cacheResultTime.' minutes'
        ]);
    }

    /**
     * Loads user's results.
     */
    public static function loadResults()
    {
        $cache = self::getCache();
        return $cache->load(Sessions::$user->id);
    }

    /**
     * Saves channel's uploads playlist.
     */
    public static function saveUploadsPlaylist($channelId, $playlistId)
    {
        $cache = self::getCache();
        $cache->save('uploads_playlist_'.$channelId, $playlistId, [
            Caching\Cache::Expire => AppConfig::cachePlaylistTime.' minutes'
        ]);
    }

    /**
     * Loads channel's uploads playlist.
     */
    public static function loadUploadsPlaylist($channelId)
    {
        $cache = self::getCache();
        return $cache->load('uploads_playlist_'.$channelId);
    }

}
