<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Model\Services;

use YouTubeRSS;
use dibi;

/**
 * Database model.
 */
class Db
{
    /* db connection status */
    public static $connected = false;

    /**
     * Initializes a db connection.
     */
    public static function init()
    {
        try {
            dibi::connect(array(
                'driver'   => YouTubeRSS\DatabaseConfig::driver,
                'host'     => YouTubeRSS\DatabaseConfig::host,
                'username' => YouTubeRSS\DatabaseConfig::username,
                'password' => YouTubeRSS\DatabaseConfig::password,
                'database' => YouTubeRSS\DatabaseConfig::database,
                'charset'  => 'utf8',
            ));
            dibi::getSubstitutes()->{'table'} = YouTubeRSS\DatabaseConfig::table;
            self::$connected = true;
        } catch (Dibi\Exception $e) {}
    }

    /**
     * Checks whether the app table exists.
     * @return bool
     */
    public static function checkTable()
    {
        try {
            $count = dibi::query('
                SELECT count(*)
                FROM information_schema.tables
                WHERE table_schema = %s', YouTubeRSS\DatabaseConfig::database,'
                    AND table_name = %s', YouTubeRSS\DatabaseConfig::table,'
            ')->fetchSingle();

            return ($count)? true : false;
        } catch (Dibi\Exception $e) {}
        return false;
    }

    /**
     * Creates the app table.
     */
    public static function createTable()
    {
        dibi::query('
            CREATE TABLE [:table:] (
                id            INT(10)     UNSIGNED    AUTO_INCREMENT    PRIMARY KEY,
                username      VARCHAR(50) NOT NULL    UNIQUE,
                password      VARCHAR(60) NOT NULL,
                accessToken   TEXT,
                refreshToken  TEXT,
                ts            TIMESTAMP   DEFAULT CURRENT_TIMESTAMP   ON UPDATE CURRENT_TIMESTAMP,
                counter       INT(10)     UNSIGNED    DEFAULT 0
            )
        ');
    }

    /**
     * Returns the admin summary.
     * @return array
     */
    public static function getSummary()
    {
        try {
            return dibi::query('
                SELECT id, username, accessToken, counter, ts
                FROM [:table:]
                ORDER BY id
            ')->fetchAll();
        } catch (Dibi\Exception $e) {}
        return array();
    }

    /**
     * Adds a new user.
     * @param string username
     * @param string hash
     * @return user id | -1
     */
    public static function insertUser($username, $hash)
    {
        dibi::begin();
        
        $count = dibi::query('
            SELECT COUNT(*)
            FROM [:table:]
            WHERE username = %s', $username,'
        ')->fetchSingle();

        if ($count !== 0) {
            dibi::commit();
            return -1;
        }

        dibi::query('
            INSERT INTO [:table:]', array(
                'username' => $username,
                'password' => $hash
            ),'
        ');
        
        $id = dibi::getInsertId();
        
        dibi::commit();

        return $id;
    }

    /**
     * Gets user's data.
     * @param string username
     * @return array(username,password,tokens)
     */
    public static function getUserData($username)
    {
        return dibi::query('
            SELECT id, username, password, accessToken, refreshToken
            FROM [:table:]
            WHERE username = %s', $username,'
        ')->fetch();
    }

    /**
     * Updates user's tokens.
     * @param string username
     * @param string accessToken
     * @param string refreshToken
     */
    public static function updateTokens($username, $accessToken, $refreshToken)
    {
        dibi::query('
            UPDATE [:table:] SET ', array(
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken
            ),'
            WHERE username = %s', $username,'
            LIMIT 1
        ');
    }

    /**
     * Updates user's use counter.
     * @param string username
     */
    public static function updateCounter($username)
    {
        dibi::query('
            UPDATE [:table:] SET ', array(
                'counter%sql' => 'counter + 1'
            ),'
            WHERE username = %s', $username,'
            LIMIT 1
        ');
    }

}
