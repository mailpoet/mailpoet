<?php declare(strict_types = 1);

namespace MailPoet\Test\Helpscout;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Helpscout\Beacon;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class BeaconTest extends \MailPoetTest {
  public $settings;
  public $beaconData;

  public function _before() {
    parent::_before();
    // create 4 users (1 confirmed, 1 subscribed, 1 unsubscribed, 1 bounced)
    $subscriberFactory = new SubscriberFactory();
    $subscriberFactory
      ->withEmail('user1@mailpoet.com')
      ->create();
    $subscriberFactory
      ->withEmail('user2@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $subscriberFactory
      ->withEmail('user3@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subscriberFactory
      ->withEmail('user4@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_BOUNCED)
      ->create();

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
    expect($this->beaconData['WP info'])->stringContainsString('WP_MEMORY_LIMIT: ' . WP_MEMORY_LIMIT);
  }

  public function testItReturnsWpMaxMemoryLimit() {
    expect($this->beaconData['WP info'])->stringContainsString('WP_MAX_MEMORY_LIMIT: ' . WP_MAX_MEMORY_LIMIT);
  }

  public function testItReturnsWpDebugValue() {
    expect($this->beaconData['WP info'])->stringContainsString('WP_DEBUG: ' . WP_DEBUG);
  }

  public function testItReturnsWpLanguage() {
    expect($this->beaconData['WP info'])->stringContainsString('WordPress language: ' . get_locale());
  }

  public function testItReturnsPhpMaxExecutionTime() {
    expect($this->beaconData['PHP info'])->stringContainsString('PHP max_execution_time: ' . ini_get('max_execution_time'));
  }

  public function testItReturnsPhpMemoryLimit() {
    expect($this->beaconData['PHP info'])->stringContainsString('PHP memory_limit: ' . ini_get('memory_limit'));
  }

  public function testItReturnsPhpUploadMaxFilesize() {
    expect($this->beaconData['PHP info'])->stringContainsString('PHP upload_max_filesize: ' . ini_get('upload_max_filesize'));
  }

  public function testItReturnsPhpPostMaxSize() {
    expect($this->beaconData['PHP info'])->stringContainsString('PHP post_max_size: ' . ini_get('post_max_size'));
  }

  public function testItReturnsIfWpIsMultisite() {
    expect($this->beaconData['Multisite environment?'])->equals(is_multisite() ? 'Yes' : 'No');
  }

  public function testItReturnsCurrentThemeNameAndVersion() {
    $currentTheme = wp_get_theme();
    $name = $currentTheme->get('Name');
    $version = $currentTheme->get('Version');
    $this->assertIsString($name);
    $this->assertIsString($version);
    expect($version)->string();
    expect($this->beaconData['Current Theme'])->stringContainsString($name);
    expect($this->beaconData['Current Theme'])->stringContainsString($version);
  }

  public function testItReturnsActivePlugins() {
    $activePlugins = get_option('active_plugins');
    $this->assertIsArray($activePlugins);
    expect($this->beaconData['Active Plugin names'])->equals(join(", ", $activePlugins));
  }

  public function testItReturnsSendingMethodDetails() {
    $mta = $this->settings->get('mta');
    expect($this->beaconData['Sending Method'])->equals($mta['method']);
    expect($this->beaconData['Sending Frequency'])->stringContainsString($mta['frequency']['emails'] . ' emails');
    expect($this->beaconData['Sending Frequency'])->stringContainsString($mta['frequency']['interval'] . ' minutes');
  }

  public function testItReturnsSomeSettings() {
    expect($this->beaconData['MailPoet sending info'])->stringContainsString('Task Scheduler method: ' . $this->settings->get('cron_trigger.method'));
    expect($this->beaconData['MailPoet sending info'])->stringContainsString('Default FROM address: ' . $this->settings->get('sender.address'));
    expect($this->beaconData['MailPoet sending info'])->stringContainsString('Default Reply-To address: ' . $this->settings->get('reply_to.address'));
    expect($this->beaconData['MailPoet sending info'])->stringContainsString('Bounce Email Address: ' . $this->settings->get('bounce.address'));
    expect($this->beaconData['Plugin installed at'])->equals($this->settings->get('installed_at'));
  }

  public function testItReturnsTransactionalEmailSendingMethod() {
    $this->settings->set('send_transactional_emails', '');
    $beacon = $this->diContainer->get(Beacon::class);
    expect($beacon->getData()['MailPoet sending info'])->stringContainsString("Send all site's emails with: default WordPress sending method");
    $this->settings->set('send_transactional_emails', '1');
    expect($beacon->getData()['MailPoet sending info'])->stringContainsString("Send all site's emails with: current sending method");
  }

  public function testItReturnsTotalNumberOfSubscribers() {
    // unsubscribed users are not taken into account
    expect($this->beaconData['Total number of subscribers'])->equals(2);
  }

  public function testItReturnsWebserverInformation() {
    expect($this->beaconData['Web server'])->equals(
      (!empty($_SERVER["SERVER_SOFTWARE"])) ? sanitize_text_field(wp_unslash($_SERVER["SERVER_SOFTWARE"])) : 'N/A'
    );
  }

  public function testItReturnsServerOSInformation() {
    expect($this->beaconData['Server OS'])->equals(utf8_encode(php_uname()));
  }

  public function testItReturnsCronPingUrl() {
    expect($this->beaconData['MailPoet sending info'])->stringContainsString('&action=ping');
    // cron ping URL should react to custom filters
    $filter = function($url) {
      return str_replace(home_url(), 'http://custom_url/', $url);
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_cron_request_url', $filter);
    $beaconData = $this->beaconData = $this->diContainer->get(Beacon::class)->getData();
    expect($beaconData['MailPoet sending info'])->regExp('!http:\/\/custom_url\/!');
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

  public function testItMasksPremiumKey() {
    $this->settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, 'f5c08f56464665c99fb462fa584398b5');
    $expectedResult = 'f5c08f56464665c9****************';
    $beaconData = $this->diContainer->get(Beacon::class)->getData(true);

    $this->assertSame($expectedResult, $beaconData['MailPoet Premium/MSS key']);
  }
}
