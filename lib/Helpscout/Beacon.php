<?php
namespace MailPoet\Helpscout;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Beacon {
  static function getData() {
    global $wpdb;
    $db_version = $wpdb->get_var('SELECT @@VERSION');
    $mta = Setting::getValue('mta');
    $current_theme = wp_get_theme();
    $current_user = wp_get_current_user();

    return array(
      'name' => $current_user->display_name,
      'email' => $current_user->user_email,
      'PHP version' => PHP_VERSION,
      'MailPoet version' => MAILPOET_VERSION,
      'WordPress version' => get_bloginfo('version'),
      'Database version' => $db_version,
      'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
      'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
      'WP_DEBUG' => WP_DEBUG,
      'PHP max_execution_time' => ini_get('max_execution_time'),
      'PHP memory_limit' => ini_get('memory_limit'),
      'PHP upload_max_filesize' => ini_get('upload_max_filesize'),
      'PHP post_max_size' => ini_get('post_max_size'),
      'WordPress language' => get_locale(),
      'Multisite environment?' => (is_multisite() ? 'Yes' : 'No'),
      'Current Theme' => $current_theme->get('Name').
        ' (version '.$current_theme->get('Version').')',
      'Active Plugin names' => join(", ", get_option('active_plugins')),
      'Sending Method' => $mta['method'],
      'Sending Frequency' => sprintf('%d emails every %d minutes',
        $mta['frequency']['emails'],
        $mta['frequency']['interval']
      ),
      'Task Scheduler method' => Setting::getValue('cron_trigger.method'),
      'Default FROM address' => Setting::getValue('sender.address'),
      'Default Reply-To address' => Setting::getValue('reply_to.address'),
      'Bounce Email Address' => Setting::getValue('bounce.address'),
      'Total number of subscribers' =>  Subscriber::getTotalSubscribers()
    );
  }
}