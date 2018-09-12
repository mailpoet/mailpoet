<?php

use MailPoet\DI\ContainerFactory;

if(!defined('ABSPATH') || empty($mailpoet_plugin)) exit;

require_once($mailpoet_plugin['autoloader']);

define('MAILPOET_VERSION', $mailpoet_plugin['version']);

$container = ContainerFactory::getContainer();
$container->setParameter('mailpoet.plugin_data', [
  'file' => $mailpoet_plugin['filename'],
  'version' => $mailpoet_plugin['version']
]);
$container->compile();

$initializer = $container->get('initializer');
$initializer->init();
