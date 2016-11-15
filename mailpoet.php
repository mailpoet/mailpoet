<?php
if(!defined('ABSPATH')) exit;

use MailPoet\Config\Initializer;

/*
 * Plugin Name: MailPoet
 * Version: 3.0.0-beta.3
 * Plugin URI: http://www.mailpoet.com
 * Description: Create and send beautiful email newsletters, autoresponders, and post notifications without leaving WordPress. This is a beta version of our brand new plugin!
 * Author: MailPoet
 * Author URI: http://www.mailpoet.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: mailpoet
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author MailPoet
 * @since 0.0.8
 */

require 'vendor/autoload.php';
define('MAILPOET_VERSION', '3.0.0-beta.3');
try {
  $initializer = new Initializer(
    array(
      'file' => __FILE__,
      'version' => MAILPOET_VERSION
    )
  );
  $initializer->init();
} catch(\Exception $e) {
  Initializer::handleFailedInitialization($e->getMessage());
}