<?php
if(!defined('ABSPATH')) exit;

use MailPoet\Config\Initializer;

/*
 * Plugin Name: MailPoet
 * Version: 3.0.0-beta.4
 * Plugin URI: http://www.mailpoet.com
 * Description: Create and send beautiful email newsletters, autoresponders, and post notifications without leaving WordPress. This is a beta version of our brand new plugin!
 * Author: MailPoet
 * Author URI: http://www.mailpoet.com
 * Requires at least: 4.0
 * Tested up to: 4.6.1
 *
 * Text Domain: mailpoet
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author MailPoet
 * @since 0.0.8
 */

$mailpoet_loader = dirname(__FILE__) . '/vendor/autoload.php';
if(file_exists($mailpoet_loader)) {
  require $mailpoet_loader;
  define('MAILPOET_VERSION', '3.0.0-beta.4');
  $initializer = new Initializer(
    array(
      'file' => __FILE__,
      'version' => MAILPOET_VERSION
    )
  );
  $initializer->init();
} else {
  add_action('admin_notices', function() {
    $notice = __('MailPoet cannot start because it is missing core files. Please reinstall the plugin.', 'mailpoet');
    printf('<div class="error"><p>%1$s</p></div>', $notice);
  });
}
