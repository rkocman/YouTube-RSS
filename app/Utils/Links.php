<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Utils;

/**
 * Link generator for the app.
 */
class Links
{

    /**
     * Generates a link.
     */
    public static function generate($section = NULL, $action = NULL, $id = NULL)
    {
        $link = "";
        if (isset($section)) {
            $link .= '?section='.urlencode($section);
        }
        if (isset($action)) {
            $link .= '&action='.urlencode($action);
        }
        if (isset($id)) {
            $link .= '&id='.urlencode($id);
        }
        return $link;
    }

    /**
     * Generates a full link.
     */
    public static function generateFull($section = NULL, $action = NULL, $id = NULL)
    {
        $params = self::generate($section, $action, $id);
        return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'],'?').$params;
    }

}
