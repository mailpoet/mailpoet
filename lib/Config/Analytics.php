<?php
namespace MailPoet\Config;
use MailPoet\Analytics\Reporter;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Analytics {
  function __construct() {
  }

  function init() {
    add_action('admin_enqueue_scripts', array($this, 'setupAdminDependencies'));
  }

  function setupAdminDependencies() {
    if(Setting::getValue('send_analytics_now', false)) {
      $analytics = new Reporter();
      wp_enqueue_script(
        'analytics',
        Env::$assets_url . '/js/lib/analytics.js',
        array(),
        Env::$version
      );
      wp_localize_script(
        'analytics',
        'mailpoet_analytics_data',
        $analytics->getData()
      );
    }
  }
}
