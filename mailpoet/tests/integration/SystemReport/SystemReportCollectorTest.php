<?php declare(strict_types = 1);

namespace MailPoet\Test\SystemReport;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\SystemReport\SystemReportCollector;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class SystemReportCollectorTest extends \MailPoetTest {
  public $settings;
  public $systemInfoData;

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

    $this->systemInfoData = $this->diContainer->get(SystemReportCollector::class)->getData();
    $this->settings = SettingsController::getInstance();
  }

  public function testItReturnsPhpVersion() {
    verify($this->systemInfoData['PHP version'])->equals(PHP_VERSION);
  }

  public function testItReturnsMailpoetVersion() {
    verify($this->systemInfoData['MailPoet Free version'])->equals(MAILPOET_VERSION);
  }

  public function testItReturnsWordpressVersion() {
    verify($this->systemInfoData['WordPress version'])->equals(get_bloginfo('version'));
  }

  public function testItReturnsDatabaseVersion() {
    global $wpdb;
    $dbVersion = $wpdb->get_var('SELECT @@VERSION');
    verify($this->systemInfoData['Database version'])->equals($dbVersion);
  }

  public function testItReturnsWpMemoryLimit() {
    verify($this->systemInfoData['WP info'])->stringContainsString('WP_MEMORY_LIMIT: ' . WP_MEMORY_LIMIT);
  }

  public function testItReturnsWpMaxMemoryLimit() {
    verify($this->systemInfoData['WP info'])->stringContainsString('WP_MAX_MEMORY_LIMIT: ' . WP_MAX_MEMORY_LIMIT);
  }

  public function testItReturnsWpDebugValue() {
    verify($this->systemInfoData['WP info'])->stringContainsString('WP_DEBUG: ' . WP_DEBUG);
  }

  public function testItReturnsWpLanguage() {
    verify($this->systemInfoData['WP info'])->stringContainsString('WordPress language: ' . get_locale());
  }

  public function testItReturnsPhpMaxExecutionTime() {
    verify($this->systemInfoData['PHP info'])->stringContainsString('PHP max_execution_time: ' . ini_get('max_execution_time'));
  }

  public function testItReturnsPhpMemoryLimit() {
    verify($this->systemInfoData['PHP info'])->stringContainsString('PHP memory_limit: ' . ini_get('memory_limit'));
  }

  public function testItReturnsPhpUploadMaxFilesize() {
    verify($this->systemInfoData['PHP info'])->stringContainsString('PHP upload_max_filesize: ' . ini_get('upload_max_filesize'));
  }

  public function testItReturnsPhpPostMaxSize() {
    verify($this->systemInfoData['PHP info'])->stringContainsString('PHP post_max_size: ' . ini_get('post_max_size'));
  }

  public function testItReturnsIfWpIsMultisite() {
    verify($this->systemInfoData['Multisite environment?'])->equals(is_multisite() ? 'Yes' : 'No');
  }

  public function testItReturnsCurrentThemeNameAndVersion() {
    $currentTheme = wp_get_theme();
    $name = $currentTheme->get('Name');
    $version = $currentTheme->get('Version');
    $this->assertIsString($name);
    $this->assertIsString($version);
    expect($version)->string();
    verify($this->systemInfoData['Current Theme'])->stringContainsString($name);
    verify($this->systemInfoData['Current Theme'])->stringContainsString($version);
  }

  public function testItReturnsActivePlugins() {
    $activePlugins = get_option('active_plugins');
    $this->assertIsArray($activePlugins);
    verify($this->systemInfoData['Active Plugin names'])->equals(join(", ", $activePlugins));
  }

  public function testItReturnsSendingMethodDetails() {
    $mta = $this->settings->get('mta');
    verify($this->systemInfoData['Sending Method'])->equals($mta['method']);
    verify($this->systemInfoData['Sending Frequency'])->stringContainsString($mta['frequency']['emails'] . ' emails');
    verify($this->systemInfoData['Sending Frequency'])->stringContainsString($mta['frequency']['interval'] . ' minutes');
  }

  public function testItReturnsSomeSettings() {
    verify($this->systemInfoData['MailPoet sending info'])->stringContainsString('Task Scheduler method: ' . $this->settings->get('cron_trigger.method'));
    verify($this->systemInfoData['MailPoet sending info'])->stringContainsString('Default FROM address: ' . $this->settings->get('sender.address'));
    verify($this->systemInfoData['MailPoet sending info'])->stringContainsString('Default Reply-To address: ' . $this->settings->get('reply_to.address'));
    verify($this->systemInfoData['MailPoet sending info'])->stringContainsString('Bounce Email Address: ' . $this->settings->get('bounce.address'));
    verify($this->systemInfoData['Plugin installed at'])->equals($this->settings->get('installed_at'));
  }

  public function testItReturnsTransactionalEmailSendingMethod() {
    $this->settings->set('send_transactional_emails', '');
    $systemReportCollector = $this->diContainer->get(SystemReportCollector::class);
    verify($systemReportCollector->getData()['MailPoet sending info'])->stringContainsString("Send all site's emails with: default WordPress sending method");
    $this->settings->set('send_transactional_emails', '1');
    verify($systemReportCollector->getData()['MailPoet sending info'])->stringContainsString("Send all site's emails with: current sending method");
  }

  public function testItReturnsTotalNumberOfSubscribers() {
    // unsubscribed users are not taken into account
    verify($this->systemInfoData['Total number of subscribers'])->equals(2);
  }

  public function testItReturnsWebserverInformation() {
    verify($this->systemInfoData['Web server'])->equals(
      (!empty($_SERVER["SERVER_SOFTWARE"])) ? sanitize_text_field(wp_unslash($_SERVER["SERVER_SOFTWARE"])) : 'N/A'
    );
  }

  public function testItReturnsServerOSInformation() {
    verify($this->systemInfoData['Server OS'])->equals(php_uname());
  }

  public function testItReturnsCronPingUrl() {
    verify($this->systemInfoData['MailPoet sending info'])->stringContainsString('&action=ping');
    // cron ping URL should react to custom filters
    $filter = function($url) {
      return str_replace(home_url(), 'http://custom_url/', $url);
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_cron_request_url', $filter);
    $systemInfoData = $this->systemInfoData = $this->diContainer->get(SystemReportCollector::class)->getData();
    verify($systemInfoData['MailPoet sending info'])->stringMatchesRegExp('!http:\/\/custom_url\/!');
    $wp->removeFilter('mailpoet_cron_request_url', $filter);
  }

  public function testItReturnsPremiumVersion() {
    verify($this->systemInfoData['MailPoet Premium version'])->equals(
      (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A'
    );
  }

  public function testItReturnsPremiumKey() {
    verify($this->systemInfoData['MailPoet Premium/MSS key'])->equals(
      $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME) ?: $this->settings->get(Bridge::API_KEY_SETTING_NAME)
    );
  }

  public function testItMasksPremiumKey() {
    $this->settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, 'f5c08f56464665c99fb462fa584398b5');
    $expectedResult = 'f5c08f56464665c9****************';
    $systemInfoData = $this->diContainer->get(SystemReportCollector::class)->getData(true);

    $this->assertSame($expectedResult, $systemInfoData['MailPoet Premium/MSS key']);
  }
}
