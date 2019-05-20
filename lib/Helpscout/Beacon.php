<?php

namespace MailPoet\Helpscout;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\CronDaemon;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class Beacon {
  static function getData() {
    global $wpdb;
    $settings = new SettingsController();
    $db_version = $wpdb->get_var('SELECT @@VERSION');
    $mta = $settings->get('mta');
    $current_theme = WPFunctions::get()->wpGetTheme();
    $current_user = WPFunctions::get()->wpGetCurrentUser();
    $premium_key = $settings->get(Bridge::PREMIUM_KEY_SETTING_NAME) ?: $settings->get(Bridge::API_KEY_SETTING_NAME);
    return [
      'name' => $current_user->display_name,
      'email' => $current_user->user_email,
      'PHP version' => PHP_VERSION,
      'MailPoet Free version' => MAILPOET_VERSION,
      'MailPoet Premium version' => (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A',
      'MailPoet Premium/MSS key' => $premium_key,
      'WordPress version' => WPFunctions::get()->getBloginfo('version'),
      'Database version' => $db_version,
      'Web server' => (!empty($_SERVER["SERVER_SOFTWARE"])) ? $_SERVER["SERVER_SOFTWARE"] : 'N/A',
      'Server OS' => (function_exists('php_uname')) ? utf8_encode(php_uname()) : 'N/A',
      'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
      'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
      'WP_DEBUG' => WP_DEBUG,
      'PHP max_execution_time' => ini_get('max_execution_time'),
      'PHP memory_limit' => ini_get('memory_limit'),
      'PHP upload_max_filesize' => ini_get('upload_max_filesize'),
      'PHP post_max_size' => ini_get('post_max_size'),
      'WordPress language' => WPFunctions::get()->getLocale(),
      'Multisite environment?' => (is_multisite() ? 'Yes' : 'No'),
      'Current Theme' => $current_theme->get('Name') .
        ' (version ' . $current_theme->get('Version') . ')',
      'Active Plugin names' => join(", ", WPFunctions::get()->getOption('active_plugins')),
      'Sending Method' => $mta['method'],
      'Sending Frequency' => sprintf('%d emails every %d minutes',
        $mta['frequency']['emails'],
        $mta['frequency']['interval']
      ),
      'Task Scheduler method' => $settings->get('cron_trigger.method'),
      'Cron ping URL' => CronHelper::getCronUrl(
        CronDaemon::ACTION_PING
      ),
      'Default FROM address' => $settings->get('sender.address'),
      'Default Reply-To address' => $settings->get('reply_to.address'),
      'Bounce Email Address' => $settings->get('bounce.address'),
      'Total number of subscribers' => Subscriber::getTotalSubscribers(),
      'Plugin installed at' => $settings->get('installed_at'),
    ];
  }
}
