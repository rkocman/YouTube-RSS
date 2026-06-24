<?php

/**
 * Metro RSS
 * Author: Radim Kocman
 */

namespace MetroRSS\Presenters;

use MetroRSS\Utils\Params;

/**
 * Request router.
 */
class Router
{

  /**
   * Selects a fitting presenter.
   */
  public static function run()
  {
    // Admin presenter
    if (Params::$section == 'admin') {
      AdminPresenter::run();
      return;
    }

    // RSS presenter
    if (Params::$section == 'rss') {
      RSSPresenter::run();
      return;
    }

    // App presenter
    AppPresenter::run();
  }
  
}
