<?php

/**
 * YouTube RSS
 * Author: Radim Kocman
 */

namespace YouTubeRSS;

use Tracy\Debugger;
use Nette\Loaders\RobotLoader;

// load a config
require_once __DIR__.'/config.php';

// composer autoloader
require_once __DIR__.'/vendor/autoload.php';

// tracy debugger
$mode = (AppConfig::devel)? Debugger::DEVELOPMENT : Debugger::PRODUCTION;
Debugger::enable($mode, __DIR__.'/log');
Debugger::$maxDepth = 6;
Debugger::$maxLength = 500;
\Tracy\Bridges\Nette\Bridge::initialize();
\Latte\Bridges\Tracy\BlueScreenPanel::initialize();

// robot loader for the app
$loader = new RobotLoader;
$loader->addDirectory(__DIR__.'/app');
$loader->setTempDirectory(__DIR__.'/temp');
$loader->register();

// start the app
Engine::Run();
