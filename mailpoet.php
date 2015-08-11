<?php
use \MailPoet\Config\Initializer;

if (!defined('ABSPATH')) exit;

/*
 * Plugin Name: MailPoet
 * Version: 1.0
 * Plugin URI: http://www.mailpoet.com
 * Description: MailPoet Newsletters.
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
 * @since 1.0.0
 */

require 'vendor/autoload.php';

define('MAILPOET_VERSION', '1.0.0');
define('MAILPOET', 'wysija-newsletters');

$initializer = new Initializer(array(
    'file' => __FILE__,
    'version' => MAILPOET_VERSION
  ));
$initializer->init();
