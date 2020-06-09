<?php

/*
 * Plugin Name: MailPoet 3 (New)
 * Version: 3.47.4
 * Plugin URI: http://www.mailpoet.com
 * Description: Create and send newsletters, post notifications and welcome emails from your WordPress.
 * Author: MailPoet
 * Author URI: http://www.mailpoet.com
 * Requires at least: 4.7
 * Tested up to: 5.4
 *
 * @package WordPress
 * @author MailPoet
 * @since 3.0.0-beta.1
 */

$mailpoetPlugin = [
  'version' => '3.47.4',
  'filename' => __FILE__,
  'path' => dirname(__FILE__),
  'autoloader' => dirname(__FILE__) . '/vendor/autoload.php',
  'initializer' => dirname(__FILE__) . '/mailpoet_initializer.php',
];

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
if (version_compare(phpversion(), '7.0.0', '<')) {
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
  $noticeP1 = __('MailPoet requires PHP version 7.0 or newer (7.3 recommended). You are running version [version].', 'mailpoet');
  $noticeP1 = str_replace('[version]', phpversion(), $noticeP1);

  $noticeP2 = __('Please read our [link]instructions[/link] on how to upgrade your site.', 'mailpoet');
  $noticeP2 = str_replace(
    '[link]',
    '<a href="https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version" target="_blank">',
    $noticeP2
  );
  $noticeP2 = str_replace('[/link]', '</a>', $noticeP2);

  $noticeP3 = __('If you can’t upgrade the PHP version, [link]install this version[/link] of MailPoet. Remember not to update MailPoet ever again!', 'mailpoet');
  $noticeP3 = str_replace(
    '[link]',
    '<a href="https://downloads.wordpress.org/plugin/mailpoet.3.44.0.zip" target="_blank">',
    $noticeP3
  );
  $noticeP3 = str_replace('[/link]', '</a>', $noticeP3);

  printf('<div class="error"><p><strong>%s</strong></p><p>%s</p><p>%s</p></div>', $noticeP1, $noticeP2, $noticeP3);
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
if (!file_exists($mailpoetPlugin['autoloader']) || !file_exists($mailpoetPlugin['initializer'])) {
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
require_once($mailpoetPlugin['initializer']);
