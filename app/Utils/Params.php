<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS\Utils;

/**
 * Handler for the app parameters.
 */
class Params
{
  // Parameters used for navigation in the app.
  public static $section;
  public static $action;
  public static $id;
  
  // Parameter used for the Google authentication.
  public static $code;
  
  /**
   * Loads parameters from the page reguest.
   */
  public static function init()
  {
    self::$section = isset($_GET['section'])? $_GET['section'] : '';
    self::$action = isset($_GET['action'])? $_GET['action'] : '';
    self::$id = isset($_GET['id'])? $_GET['id'] : '';
    
    self::$code = isset($_GET['code'])? $_GET['code'] : null;
  }
  
}
