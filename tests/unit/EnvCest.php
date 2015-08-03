<?php
use \UnitTester;
use \MailPoet\Config\Env;

class EnvCest {
  public function _before() {
    Env::init();
  }

  public function itCanReturnThePluginPrefix() {
    expect(Env::$plugin_prefix)->equals('mailpoet_');
  }

  public function itCanReturnTheDbPrefix() {
    global $wpdb;
    $db_prefix = $wpdb->prefix;
    expect(Env::$db_prefix)->equals($db_prefix);
  }

  public function itCanReturnTheDbHost() {
    expect(Env::$db_host)->equals(DB_HOST);
  }

  public function itCanReturnTheDbUser() {
    expect(Env::$db_username)->equals(DB_USER);
  }

  public function itCanReturnTheDbPassword() {
    expect(Env::$db_password)->equals(DB_PASSWORD);
  }

  public function itCanReturnTheDbCharset() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    expect(Env::$db_charset)->equals($charset);
  }

  public function _after() {
  }
}
