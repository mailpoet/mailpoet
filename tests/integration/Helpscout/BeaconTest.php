<?php

namespace MailPoet\Test\Helpscout;

use MailPoet\Helpscout\Beacon;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class BeaconTest extends \MailPoetTest {
  public $settings;
  public $beaconData;

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

    $this->beaconData = $this->diContainer->get(Beacon::class)->getData();
    $this->settings = SettingsController::getInstance();
  }

  public function testItReturnsPhpVersion() {
    expect($this->beaconData['PHP version'])->equals(PHP_VERSION);
  }

  public function testItReturnsMailpoetVersion() {
    expect($this->beaconData['MailPoet Free version'])->equals(MAILPOET_VERSION);
  }

  public function testItReturnsWordpressVersion() {
    expect($this->beaconData['WordPress version'])->equals(get_bloginfo('version'));
  }

  public function testItReturnsDatabaseVersion() {
    global $wpdb;
    $dbVersion = $wpdb->get_var('SELECT @@VERSION');
    expect($this->beaconData['Database version'])->equals($dbVersion);
  }

  public function testItReturnsWpMemoryLimit() {
    expect($this->beaconData['WP_MEMORY_LIMIT'])->equals(WP_MEMORY_LIMIT);
  }

  public function testItReturnsWpMaxMemoryLimit() {
    expect($this->beaconData['WP_MAX_MEMORY_LIMIT'])->equals(WP_MAX_MEMORY_LIMIT);
  }

  public function testItReturnsWpDebugValue() {
    expect($this->beaconData['WP_DEBUG'])->equals(WP_DEBUG);
  }

  public function testItReturnsPhpMaxExecutionTime() {
    expect($this->beaconData['PHP max_execution_time'])->equals(ini_get('max_execution_time'));
  }

  public function testItReturnsPhpMemoryLimit() {
    expect($this->beaconData['PHP memory_limit'])->equals(ini_get('memory_limit'));
  }

  public function testItReturnsPhpUploadMaxFilesize() {
    expect($this->beaconData['PHP upload_max_filesize'])->equals(ini_get('upload_max_filesize'));
  }

  public function testItReturnsPhpPostMaxSize() {
    expect($this->beaconData['PHP post_max_size'])->equals(ini_get('post_max_size'));
  }

  public function testItReturnsWpLanguage() {
    expect($this->beaconData['WordPress language'])->equals(get_locale());
  }

  public function testItReturnsIfWpIsMultisite() {
    expect($this->beaconData['Multisite environment?'])->equals(is_multisite() ? 'Yes' : 'No');
  }

  public function testItReturnsCurrentThemeNameAndVersion() {
    $currentTheme = wp_get_theme();
    $name = $currentTheme->get('Name');
    $version = $currentTheme->get('Version');
    assert(is_string($name));
    assert(is_string($version));
    expect($version)->string();
    expect($this->beaconData['Current Theme'])->stringContainsString($name);
    expect($this->beaconData['Current Theme'])->stringContainsString($version);
  }

  public function testItReturnsActivePlugins() {
    expect($this->beaconData['Active Plugin names'])->equals(join(", ", get_option('active_plugins')));
  }

  public function testItReturnsSendingMethodDetails() {
    $mta = $this->settings->get('mta');
    expect($this->beaconData['Sending Method'])->equals($mta['method']);
    expect($this->beaconData['Sending Frequency'])->stringContainsString($mta['frequency']['emails'] . ' emails');
    expect($this->beaconData['Sending Frequency'])->stringContainsString($mta['frequency']['interval'] . ' minutes');
  }

  public function testItReturnsSomeSettings() {
    expect($this->beaconData['Task Scheduler method'])->equals($this->settings->get('cron_trigger.method'));
    expect($this->beaconData['Default FROM address'])->equals($this->settings->get('sender.address'));
    expect($this->beaconData['Default Reply-To address'])->equals($this->settings->get('reply_to.address'));
    expect($this->beaconData['Bounce Email Address'])->equals($this->settings->get('bounce.address'));
    expect($this->beaconData['Plugin installed at'])->equals($this->settings->get('installed_at'));
  }

  public function testItReturnsTotalNumberOfSubscribers() {
    // unsubscribed users are not taken into account
    expect($this->beaconData['Total number of subscribers'])->equals(2);
  }

  public function testItReturnsWebserverInformation() {
    expect($this->beaconData['Web server'])->equals(
      (!empty($_SERVER["SERVER_SOFTWARE"])) ? $_SERVER["SERVER_SOFTWARE"] : 'N/A'
    );
  }

  public function testItReturnsServerOSInformation() {
    expect($this->beaconData['Server OS'])->equals(utf8_encode(php_uname()));
  }

  public function testItReturnsCronPingUrl() {
    expect($this->beaconData['Cron ping URL'])->stringContainsString('&action=ping');
    // cron ping URL should react to custom filters
    $filter = function($url) {
      return str_replace(home_url(), 'http://custom_url/', $url);
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_cron_request_url', $filter);
    $beaconData = $this->beaconData = $this->diContainer->get(Beacon::class)->getData();
    expect($beaconData['Cron ping URL'])->regExp('!^http:\/\/custom_url\/!');
    $wp->removeFilter('mailpoet_cron_request_url', $filter);
  }

  public function testItReturnsPremiumVersion() {
    expect($this->beaconData['MailPoet Premium version'])->equals(
      (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A'
    );
  }

  public function testItReturnsPremiumKey() {
    expect($this->beaconData['MailPoet Premium/MSS key'])->equals(
      $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME) ?: $this->settings->get(Bridge::API_KEY_SETTING_NAME)
    );
  }
}
