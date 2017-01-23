<?php

if(!defined('ABSPATH')) exit;

/*
 * Plugin Name: MailPoet
 * Version: 3.0.0-beta.14
 * Plugin URI: http://www.mailpoet.com
 * Description: Create and send beautiful email newsletters, autoresponders, and post notifications without leaving WordPress. This is a beta version of our brand new plugin!
 * Author: MailPoet
 * Author URI: http://www.mailpoet.com
 * Requires at least: 4.6
 * Tested up to: 4.7.1
 *
 * Text Domain: mailpoet
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author MailPoet
 * @since 3.0.0-beta.1
 */

$mailpoet_plugin = array(
  'version' => '3.0.0-beta.14',
  'filename' => __FILE__,
  'path' => dirname(__FILE__),
  'autoloader' => dirname(__FILE__) . '/vendor/autoload.php',
  'initializer' => dirname(__FILE__) . '/mailpoet_initializer.php'
);

function deactivate_plugin() {
  deactivate_plugins(plugin_basename(__FILE__));
  if(!empty($_GET['activate'])) {
    unset($_GET['activate']);
  }
}

// Check for the minimum PHP version
if(version_compare(phpversion(), '5.3.0', '<')) {
  add_action('admin_notices', 'php_version_notice');
  // deactivate the plugin
  add_action('admin_init', 'deactivate_plugin');
  return;
}

// display PHP version error notice
function php_version_notice() {
  $notice = str_replace(
    '[link]',
    '<a href="//docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_version" target="_blank">',
    __('MailPoet plugin requires PHP version 5.3 or newer. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet')
  );
  $notice = str_replace('[/link]', '</a>', $notice);
  printf('<div class="error"><p>%1$s</p></div>', $notice);
}

// Check for core dependencies
if(!file_exists($mailpoet_plugin['autoloader']) || !file_exists($mailpoet_plugin['initializer'])) {
  add_action('admin_notices', 'core_dependency_notice');
  // deactivate the plugin
  add_action('admin_init', 'deactivate_plugin');
  return;
}

// display core dependency error notice
function core_dependency_notice() {
  $notice = __('MailPoet cannot start because it is missing core files. Please reinstall the plugin.', 'mailpoet');
  printf('<div class="error"><p>%1$s</p></div>', $notice);
}

// Initialize the plugin
require_once($mailpoet_plugin['initializer']);
