<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Utils;

use YouTubeRSS\AppConfig;

/**
 * Latte engine handler.
 */
class Latte
{
    /** @var \Latte\Engine */
    private static $latte = null;

    /** @return \Latte\Engine */
    private static function getLatte()
    {
        if (self::$latte === null) {
            self::$latte = new \Latte\Engine;
            self::$latte->setTempDirectory(Path::getTemp());
            self::$latte->addFilter('cleaner', function ($s) {
                if (AppConfig::removeEmoji) {
                    // This ensures compatibility with RSS readers that use older IE engines.
                    $s = preg_replace('/'
                    //.'([0-9#][\x{20E3}])|' // enclosing keycap
                    //.'[\x{00ae}\x{00a9}\x{203C}\x{2047}][\x{FE00}-\x{FEFF}]?|' // mix
                    //.'[\x{2048}\x{2049}\x{3030}\x{303D}][\x{FE00}-\x{FEFF}]?|' // mix
                    //.'[\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|' // mix
                    //.'[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|' // arrows
                    //.'[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|' // technical
                    //.'[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|' // enclosed alphanum
                    //.'[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|' // geometric shapes
                    //.'[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|' // miscellaneous
                    //.'[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|' // supplemental arrows
                    //.'[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|' // additional
                    .'[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?|' // miscellaneous emoji
                    .'[\x{1F900}-\x{1F9FF}][\x{FE00}-\x{FEFF}]?'  // additional emoji
                    .'/u', '', $s);
                }
                return $s;
            });
        }
        return self::$latte;
    }
    
    /** 
     * Renders the template.
     */
    public static function render($template, array $params = []) {
        $latte = self::getLatte();
        $latte->render(Path::getViews().$template, $params);
    }

}
