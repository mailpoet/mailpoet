<?php

use MailPoet\Config\Env;
use MailPoet\Config\Initializer;

if (!defined('ABSPATH') || empty($mailpoet_plugin)) exit;

require_once($mailpoet_plugin['autoloader']);

define('MAILPOET_VERSION', $mailpoet_plugin['version']);

Env::init($mailpoet_plugin['filename'], $mailpoet_plugin['version']);
$initializer = new Initializer();
$initializer->init();
