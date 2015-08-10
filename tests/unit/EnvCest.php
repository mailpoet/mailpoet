<?php
use \MailPoet\Config\Env;

class EnvCest {
  function _before() {
    Env::init('file', '1.0.0');
  }

  function itCanReturnThePluginPrefix() {
    expect(Env::$plugin_prefix)->equals('mailpoet_');
  }

  function itCanReturnTheDbPrefix() {
    global $wpdb;
    $db_prefix = $wpdb->prefix . 'mailpoet_';
    expect(Env::$db_prefix)->equals($db_prefix);
  }

  function itCanReturnTheDbHost() {
    expect(Env::$db_host)->equals(DB_HOST);
  }

  function itCanReturnTheDbName() {
    expect(Env::$db_name)->equals(DB_NAME);
  }

  function itCanReturnTheDbUser() {
    expect(Env::$db_username)->equals(DB_USER);
  }

  function itCanReturnTheDbPassword() {
    expect(Env::$db_password)->equals(DB_PASSWORD);
  }

  function itCanReturnTheDbCharset() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    expect(Env::$db_charset)->equals($charset);
  }

  function itCanGenerateTheDbSourceName() {
    $source_name = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    expect(Env::$db_source_name)->equals($source_name);
  }

  function _after() {
  }
}
