<?php
use MailPoet\Helpscout\Beacon;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

class BeaconTest extends MailPoetTest {
  function _before() {
    // create 4 users (1 confirmed, 1 subscribed, 1 unsubscribed, 1 bounced)
    Subscriber::createOrUpdate(array(
      'email' => 'user1@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    ));
    Subscriber::createOrUpdate(array(
      'email' => 'user2@mailpoet.com',
      'status' => Subscriber::STATUS_UNCONFIRMED
    ));
    Subscriber::createOrUpdate(array(
      'email' => 'user3@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED
    ));
    Subscriber::createOrUpdate(array(
      'email' => 'user4@mailpoet.com',
      'status' => Subscriber::STATUS_BOUNCED
    ));

    $this->beacon_data = Beacon::getData();
  }

  function testItReturnsPhpVersion() {
    expect($this->beacon_data['PHP version'])->equals(PHP_VERSION);
  }

  function testItReturnsMailpoetVersion() {
    expect($this->beacon_data['MailPoet Free version'])->equals(MAILPOET_VERSION);
  }

  function testItReturnsWordpressVersion() {
    expect($this->beacon_data['WordPress version'])->equals(get_bloginfo('version'));
  }

  function testItReturnsDatabaseVersion() {
    global $wpdb;
    $db_version = $wpdb->get_var('SELECT @@VERSION');
    expect($this->beacon_data['Database version'])->equals($db_version);
  }

  function testItReturnsWpMemoryLimit() {
    expect($this->beacon_data['WP_MEMORY_LIMIT'])->equals(WP_MEMORY_LIMIT);
  }

  function testItReturnsWpMaxMemoryLimit() {
    expect($this->beacon_data['WP_MAX_MEMORY_LIMIT'])->equals(WP_MAX_MEMORY_LIMIT);
  }

  function testItReturnsWpDebugValue() {
    expect($this->beacon_data['WP_DEBUG'])->equals(WP_DEBUG);
  }

  function testItReturnsPhpMaxExecutionTime() {
    expect($this->beacon_data['PHP max_execution_time'])->equals(ini_get('max_execution_time'));
  }

  function testItReturnsPhpMemoryLimit() {
    expect($this->beacon_data['PHP memory_limit'])->equals(ini_get('memory_limit'));
  }

  function testItReturnsPhpUploadMaxFilesize() {
    expect($this->beacon_data['PHP upload_max_filesize'])->equals(ini_get('upload_max_filesize'));
  }

  function testItReturnsPhpPostMaxSize() {
    expect($this->beacon_data['PHP post_max_size'])->equals(ini_get('post_max_size'));
  }

  function testItReturnsWpLanguage() {
    expect($this->beacon_data['WordPress language'])->equals(get_locale());
  }

  function testItReturnsIfWpIsMultisite() {
    expect($this->beacon_data['Multisite environment?'])->equals(is_multisite() ? 'Yes' : 'No');
  }

  function testItReturnsCurrentThemeNameAndVersion() {
    $current_theme = wp_get_theme();
    expect($this->beacon_data['Current Theme'])->contains($current_theme->get('Name'));
    expect($this->beacon_data['Current Theme'])->contains($current_theme->get('Version'));
  }

  function testItReturnsActivePlugins() {
    expect($this->beacon_data['Active Plugin names'])->equals(join(", ", get_option('active_plugins')));
  }

  function testItReturnsSendingMethodDetails() {
    $mta = Setting::getValue('mta');
    expect($this->beacon_data['Sending Method'])->equals($mta['method']);
    expect($this->beacon_data['Sending Frequency'])->contains($mta['frequency']['emails'].' emails');
    expect($this->beacon_data['Sending Frequency'])->contains($mta['frequency']['interval'].' minutes');
  }

  function testItReturnsSomeSettings() {
    expect($this->beacon_data['Task Scheduler method'])->equals(Setting::getValue('cron_trigger.method'));
    expect($this->beacon_data['Default FROM address'])->equals(Setting::getValue('sender.address'));
    expect($this->beacon_data['Default Reply-To address'])->equals(Setting::getValue('reply_to.address'));
    expect($this->beacon_data['Bounce Email Address'])->equals(Setting::getValue('bounce.address'));
  }

  function testItReturnsTotalNumberOfSubscribers() {
    // unsubscribed users are not taken into account
    expect($this->beacon_data['Total number of subscribers'])->equals(2);
  }

  function testItReturnsWebserverInformation() {
    expect($this->beacon_data['Web server'])->equals(
      (!empty($_SERVER["SERVER_SOFTWARE"])) ? $_SERVER["SERVER_SOFTWARE"] : 'N/A'
    );
  }

  function testItReturnsServerOSInformation() {
    expect($this->beacon_data['Server OS'])->equals(utf8_encode(php_uname()));
  }

  function testItReturnsCronPingResponse() {
    expect($this->beacon_data['Cron ping URL'])->contains('&action=ping');
  }

  function testItReturnsPremiumVersion() {
    expect($this->beacon_data['MailPoet Premium version'])->equals(
      (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A'
    );
  }
}