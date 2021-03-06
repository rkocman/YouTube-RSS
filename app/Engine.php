<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS;

use Tracy\Debugger;

/**
 * This class controls the whole app.
 */
class Engine
{
    
    public static function run()
    {
        try {

            Utils\Params::init();
            Utils\Sessions::init();
            Model\Services\Db::init();
            Model\Services\YouTube::init();

            Presenters\Router::run();
            
        /// Exception handling
        } catch (\Exception $e) {
            if (AppConfig::devel) {
                throw $e;
            }
            Debugger::log($e, Debugger::ERROR);
            if ($e instanceof \Dibi\Exception) {
                Presenters\AppPresenter::showError('Some SQL error has occurred!');
            } elseif ($e instanceof \Google_Exception) {
                Presenters\AppPresenter::showError('A Google service error has occurred!');
            } else {
                Presenters\AppPresenter::showError('Some unexpected error has occurred!');
            }
        }
        ///
    }

}
