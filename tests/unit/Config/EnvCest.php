<?php
use MailPoet\Config\Env;

class EnvCest {
  function _before() {
    Env::init('file', '1.0.0');
  }

  function itCanReturnPluginPrefix() {
    expect(Env::$plugin_prefix)->equals('mailpoet_');
  }

  function itCanReturnDbPrefix() {
    global $wpdb;
    $db_prefix = $wpdb->prefix . 'mailpoet_';
    expect(Env::$db_prefix)->equals($db_prefix);
  }

  function itCanReturnDbHost() {
    if(preg_match('/(?=:\d+$)/', DB_HOST)) {
      expect(Env::$db_host)->equals(explode(':', DB_HOST)[0]);
    } else expect(Env::$db_host)->equals(DB_HOST);
  }

  function itCanReturnDbPort() {
    if(preg_match('/(?=:\d+$)/', DB_HOST)) {
      expect(Env::$db_port)->equals(explode(':', DB_HOST)[1]);
    } else expect(Env::$db_port)->equals(3306);
  }

  function itCanReturnSocket() {
    if(!preg_match('/(?=:\d+$)/', DB_HOST)
      && preg_match('/:/', DB_HOST)
    ) {
      expect(Env::$db_socket)->true();
    } else expect(Env::$db_socket)->false();
  }

  function itCanReturnDbName() {
    expect(Env::$db_name)->equals(DB_NAME);
  }

  function itCanReturnDbUser() {
    expect(Env::$db_username)->equals(DB_USER);
  }

  function itCanReturnDbPassword() {
    expect(Env::$db_password)->equals(DB_PASSWORD);
  }

  function itCanReturnDbCharset() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    expect(Env::$db_charset)->equals($charset);
  }

  function itCanGenerateDbSourceName() {
    $source_name = ((!ENV::$db_socket) ? 'mysql:host=' : 'mysql:unix_socket=') .
      ENV::$db_host . ';port=' . ENV::$db_port . ';dbname=' . DB_NAME;
    expect(Env::$db_source_name)->equals($source_name);
  }
}
