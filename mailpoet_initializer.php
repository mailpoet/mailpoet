<?php

use MailPoet\Config\Env;
use Tracy\Debugger;

if (!defined('ABSPATH') || empty($mailpoet_plugin)) exit;

require_once($mailpoet_plugin['autoloader']);

// setup Tracy Debugger in dev mode and only for PHP version > 7.1
$tracy_path = __DIR__ . '/tools/tracy.phar';
if (WP_DEBUG && PHP_VERSION_ID >= 70100 && file_exists($tracy_path)) {
  require_once $tracy_path;

  if (getenv('MAILPOET_DISABLE_TRACY_BAR')) {
    Debugger::$showBar = false;
  } else {
    function render_tracy() {
      ob_start();
      Debugger::renderLoader();
      $tracy_script_html = ob_get_clean();

      // strip 'async' to ensure all AJAX request are caught
      // (even when fired immediately after page starts loading)
      // see: https://github.com/nette/tracy/issues/246
      $tracy_script_html = str_replace('async', '', $tracy_script_html);

      // set higher number of displayed AJAX rows
      $max_ajax_rows = 4;
      $tracy_script_html .= "<script>window.TracyMaxAjaxRows = $max_ajax_rows;</script>\n";
      echo $tracy_script_html;
    }
    add_action('admin_enqueue_scripts', 'render_tracy', PHP_INT_MAX, 0);
    session_start();
  }
  Debugger::enable(Debugger::DEVELOPMENT);
}

define('MAILPOET_VERSION', $mailpoet_plugin['version']);

Env::init(
  $mailpoet_plugin['filename'],
  $mailpoet_plugin['version'],
  DB_HOST,
  DB_USER,
  DB_PASSWORD,
  DB_NAME
);

$initializer = MailPoet\DI\ContainerWrapper::getInstance()->get(MailPoet\Config\Initializer::class);
$initializer->init();
