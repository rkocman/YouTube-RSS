<?php

// FILL and RENAME to config.php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS;

/**
 * Database configuration.
 */
class DatabaseConfig
{
    const driver = 'mysql';
    const host = 'localhost';
    const username = 'admin';
    const password = '';
    const database = 'test';
    const table = 'youtuberss';
}

/**
 * Admin configuration.
 */
class AdminConfig
{
    const username = 'admin';
    const password = '';
}

/**
 * YouTube configuration.
 */
class YouTubeConfig
{
    const appId = '';
    const secret = '';
}

/**
 * Application configuration.
 */
class AppConfig
{
    const devel = false;
    const debugResults = false;

    const cache = false;
    const cacheTime = 120; // in minutes

    const sessionName = 'youtuberss';

    const phpTimeLimit = 300; // in seconds

    const verifySslPeer = true;

    const parallelRequests = 10; // Try a higher number for faster results.
    const videosPerChannel = 30; // max 50
    const maxResults = 200;

    // This can remove emoji icons from the content for compatibility reasons.
    const removeEmoji = false;
}
