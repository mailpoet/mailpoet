<?php

use MailPoet\Config\Initializer;

if(!defined('ABSPATH') || empty($mailpoet_plugin)) exit;

require_once($mailpoet_plugin['autoloader']);

define('MAILPOET_VERSION', $mailpoet_plugin['version']);

$initializer = new Initializer(
  array(
    'file' => $mailpoet_plugin['filename'],
    'version' => $mailpoet_plugin['version']
  )
);
$initializer->init();
