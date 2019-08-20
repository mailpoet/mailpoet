<?php

if (!defined('ABSPATH')) exit;

/*
 * Plugin Name: MailPoet 3 (New)
 * Version: 3.35.1
 * Plugin URI: http://www.mailpoet.com
 * Description: Create and send newsletters, post notifications and welcome emails from your WordPress.
 * Author: MailPoet
 * Author URI: http://www.mailpoet.com
 * Requires at least: 4.7
 * Tested up to: 5.1
 *
 * @package WordPress
 * @author MailPoet
 * @since 3.0.0-beta.1
 */

$mailpoet_plugin = array(
  'version' => '3.35.1',
  'filename' => __FILE__,
  'path' => dirname(__FILE__),
  'autoloader' => dirname(__FILE__) . '/vendor/autoload.php',
  'initializer' => dirname(__FILE__) . '/mailpoet_initializer.php'
);

function mailpoet_deactivate_plugin() {
  deactivate_plugins(plugin_basename(__FILE__));
  if (!empty($_GET['activate'])) {
    unset($_GET['activate']);
  }
}

// Check for minimum supported WP version
if (version_compare(get_bloginfo('version'), '4.6', '<')) {
  add_action('admin_notices', 'mailpoet_wp_version_notice');
  // deactivate the plugin
  add_action('admin_init', 'mailpoet_deactivate_plugin');
  return;
}

// Check for minimum supported PHP version
if (version_compare(phpversion(), '5.6.0', '<')) {
  add_action('admin_notices', 'mailpoet_php_version_notice');
  // deactivate the plugin
  add_action('admin_init', 'mailpoet_deactivate_plugin');
  return;
}

// Display WP version error notice
function mailpoet_wp_version_notice() {
  $notice = str_replace(
    '[link]',
    '<a href="https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#wp_version" target="_blank">',
    __('MailPoet plugin requires WordPress version 4.6 or newer. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet')
  );
  $notice = str_replace('[/link]', '</a>', $notice);
  printf('<div class="error"><p>%1$s</p></div>', $notice);
}

// Display PHP version error notice
function mailpoet_php_version_notice() {
  $notice = str_replace(
    '[link]',
    '<a href="https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_version" target="_blank">',
    __('MailPoet requires PHP version 5.6 or newer (version 7.2 recommended). Please read our [link]instructions[/link] on how to upgrade your site.', 'mailpoet')
  );
  $notice = str_replace('[/link]', '</a>', $notice);
  printf('<div class="error"><p>%1$s</p></div>', $notice);
}

if (isset($_SERVER['SERVER_SOFTWARE']) && strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'microsoft-iis') !== false) {
  add_action('admin_notices', 'mailpoet_microsoft_iis_notice');
  // deactivate the plugin
  add_action('admin_init', 'mailpoet_deactivate_plugin');
  return;
}

// Display IIS server error notice
function mailpoet_microsoft_iis_notice() {
  $notice = __("MailPoet plugin cannot run under Microsoft's Internet Information Services (IIS) web server. We recommend that you use a web server powered by Apache or NGINX.", 'mailpoet');
  printf('<div class="error"><p>%1$s</p></div>', $notice);
}

// Check for presence of core dependencies
if (!file_exists($mailpoet_plugin['autoloader']) || !file_exists($mailpoet_plugin['initializer'])) {
  add_action('admin_notices', 'mailpoet_core_dependency_notice');
  // deactivate the plugin
  add_action('admin_init', 'mailpoet_deactivate_plugin');
  return;
}

// Display missing core dependencies error notice
function mailpoet_core_dependency_notice() {
  $notice = __('MailPoet cannot start because it is missing core files. Please reinstall the plugin.', 'mailpoet');
  printf('<div class="error"><p>%1$s</p></div>', $notice);
}

// Initialize plugin
require_once($mailpoet_plugin['initializer']);
