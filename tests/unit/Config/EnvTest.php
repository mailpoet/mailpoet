<?php
use MailPoet\Config\Env;

class EnvTest extends MailPoetTest {
  function _before() {
    Env::init('file', '1.0.0');
  }

  function testItCanReturnPluginPrefix() {
    expect(Env::$plugin_prefix)->equals('mailpoet_');
  }

  function testItCanReturnDbPrefix() {
    global $wpdb;
    $db_prefix = $wpdb->prefix . 'mailpoet_';
    expect(Env::$db_prefix)->equals($db_prefix);
  }

  function testItCanReturnDbHost() {
    if(preg_match('/(?=:\d+$)/', DB_HOST)) {
      expect(Env::$db_host)->equals(explode(':', DB_HOST)[0]);
    } else expect(Env::$db_host)->equals(DB_HOST);
  }

  function testItCanReturnDbPort() {
    if(preg_match('/(?=:\d+$)/', DB_HOST)) {
      expect(Env::$db_port)->equals(explode(':', DB_HOST)[1]);
    } else expect(Env::$db_port)->equals(3306);
  }

  function testItCanReturnSocket() {
    if(!preg_match('/(?=:\d+$)/', DB_HOST)
      && preg_match('/:/', DB_HOST)
    ) {
      expect(Env::$db_socket)->true();
    } else expect(Env::$db_socket)->false();
  }

  function testItCanReturnDbName() {
    expect(Env::$db_name)->equals(DB_NAME);
  }

  function testItCanReturnDbUser() {
    expect(Env::$db_username)->equals(DB_USER);
  }

  function testItCanReturnDbPassword() {
    expect(Env::$db_password)->equals(DB_PASSWORD);
  }

  function testItCanReturnDbCharset() {
    global $wpdb;
    $charset = $wpdb->charset;
    expect(Env::$db_charset)->equals($charset);
  }

  function testItCanReturnDbCollation() {
    global $wpdb;
    $collation = $wpdb->collate;
    expect(Env::$db_collation)->equals($collation);
  }

  function testItCanReturnDbCharsetCollate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    expect(Env::$db_charset_collate)->equals($charset_collate);
  }

  function testItCanGenerateDbSourceName() {
    $source_name = ((!ENV::$db_socket) ? 'mysql:host=' : 'mysql:unix_socket=') .
      ENV::$db_host . ';port=' . ENV::$db_port . ';dbname=' . DB_NAME;
    expect(Env::$db_source_name)->equals($source_name);
  }

  function testItCanGetDbTimezoneOffset() {
    expect(Env::getDbTimezoneOffset('+1.5'))->equals("+01:30");
    expect(Env::getDbTimezoneOffset('+11'))->equals("+11:00");
    expect(Env::getDbTimezoneOffset('-5.5'))->equals("-05:30");
  }
}
