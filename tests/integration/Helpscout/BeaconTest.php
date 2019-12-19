<?php

namespace MailPoet\Test\Helpscout;

use MailPoet\Helpscout\Beacon;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class BeaconTest extends \MailPoetTest {
  public $settings;
  public $beacon_data;
  public function _before() {
    parent::_before();
    // create 4 users (1 confirmed, 1 subscribed, 1 unsubscribed, 1 bounced)
    Subscriber::createOrUpdate([
      'email' => 'user1@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    Subscriber::createOrUpdate([
      'email' => 'user2@mailpoet.com',
      'status' => Subscriber::STATUS_UNCONFIRMED,
    ]);
    Subscriber::createOrUpdate([
      'email' => 'user3@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    Subscriber::createOrUpdate([
      'email' => 'user4@mailpoet.com',
      'status' => Subscriber::STATUS_BOUNCED,
    ]);

    $this->beacon_data = $this->di_container->get(Beacon::class)->getData();
    $this->settings = SettingsController::getInstance();
  }

  public function testItReturnsPhpVersion() {
    expect($this->beacon_data['PHP version'])->equals(PHP_VERSION);
  }

  public function testItReturnsMailpoetVersion() {
    expect($this->beacon_data['MailPoet Free version'])->equals(MAILPOET_VERSION);
  }

  public function testItReturnsWordpressVersion() {
    expect($this->beacon_data['WordPress version'])->equals(get_bloginfo('version'));
  }

  public function testItReturnsDatabaseVersion() {
    global $wpdb;
    $db_version = $wpdb->get_var('SELECT @@VERSION');
    expect($this->beacon_data['Database version'])->equals($db_version);
  }

  public function testItReturnsWpMemoryLimit() {
    expect($this->beacon_data['WP_MEMORY_LIMIT'])->equals(WP_MEMORY_LIMIT);
  }

  public function testItReturnsWpMaxMemoryLimit() {
    expect($this->beacon_data['WP_MAX_MEMORY_LIMIT'])->equals(WP_MAX_MEMORY_LIMIT);
  }

  public function testItReturnsWpDebugValue() {
    expect($this->beacon_data['WP_DEBUG'])->equals(WP_DEBUG);
  }

  public function testItReturnsPhpMaxExecutionTime() {
    expect($this->beacon_data['PHP max_execution_time'])->equals(ini_get('max_execution_time'));
  }

  public function testItReturnsPhpMemoryLimit() {
    expect($this->beacon_data['PHP memory_limit'])->equals(ini_get('memory_limit'));
  }

  public function testItReturnsPhpUploadMaxFilesize() {
    expect($this->beacon_data['PHP upload_max_filesize'])->equals(ini_get('upload_max_filesize'));
  }

  public function testItReturnsPhpPostMaxSize() {
    expect($this->beacon_data['PHP post_max_size'])->equals(ini_get('post_max_size'));
  }

  public function testItReturnsWpLanguage() {
    expect($this->beacon_data['WordPress language'])->equals(get_locale());
  }

  public function testItReturnsIfWpIsMultisite() {
    expect($this->beacon_data['Multisite environment?'])->equals(is_multisite() ? 'Yes' : 'No');
  }

  public function testItReturnsCurrentThemeNameAndVersion() {
    $current_theme = wp_get_theme();
    expect($this->beacon_data['Current Theme'])->contains($current_theme->get('Name'));
    expect($this->beacon_data['Current Theme'])->contains($current_theme->get('Version'));
  }

  public function testItReturnsActivePlugins() {
    expect($this->beacon_data['Active Plugin names'])->equals(join(", ", get_option('active_plugins')));
  }

  public function testItReturnsSendingMethodDetails() {
    $mta = $this->settings->get('mta');
    expect($this->beacon_data['Sending Method'])->equals($mta['method']);
    expect($this->beacon_data['Sending Frequency'])->contains($mta['frequency']['emails'] . ' emails');
    expect($this->beacon_data['Sending Frequency'])->contains($mta['frequency']['interval'] . ' minutes');
  }

  public function testItReturnsSomeSettings() {
    expect($this->beacon_data['Task Scheduler method'])->equals($this->settings->get('cron_trigger.method'));
    expect($this->beacon_data['Default FROM address'])->equals($this->settings->get('sender.address'));
    expect($this->beacon_data['Default Reply-To address'])->equals($this->settings->get('reply_to.address'));
    expect($this->beacon_data['Bounce Email Address'])->equals($this->settings->get('bounce.address'));
    expect($this->beacon_data['Plugin installed at'])->equals($this->settings->get('installed_at'));
  }

  public function testItReturnsTotalNumberOfSubscribers() {
    // unsubscribed users are not taken into account
    expect($this->beacon_data['Total number of subscribers'])->equals(2);
  }

  public function testItReturnsWebserverInformation() {
    expect($this->beacon_data['Web server'])->equals(
      (!empty($_SERVER["SERVER_SOFTWARE"])) ? $_SERVER["SERVER_SOFTWARE"] : 'N/A'
    );
  }

  public function testItReturnsServerOSInformation() {
    expect($this->beacon_data['Server OS'])->equals(utf8_encode(php_uname()));
  }

  public function testItReturnsCronPingUrl() {
    expect($this->beacon_data['Cron ping URL'])->contains('&action=ping');
    // cron ping URL should react to custom filters
    $filter = function($url) {
      return str_replace(home_url(), 'http://custom_url/', $url);
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_cron_request_url', $filter);
    $beacon_data = $this->beacon_data = $this->di_container->get(Beacon::class)->getData();
    expect($beacon_data['Cron ping URL'])->regExp('!^http:\/\/custom_url\/!');
    $wp->removeFilter('mailpoet_cron_request_url', $filter);
  }

  public function testItReturnsPremiumVersion() {
    expect($this->beacon_data['MailPoet Premium version'])->equals(
      (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A'
    );
  }

  public function testItReturnsPremiumKey() {
    expect($this->beacon_data['MailPoet Premium/MSS key'])->equals(
      $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME) ?: $this->settings->get(Bridge::API_KEY_SETTING_NAME)
    );
  }
}
