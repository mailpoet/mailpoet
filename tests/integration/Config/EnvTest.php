<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\Env;

class EnvTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    // Back up original environment values
    $this->file = Env::$file;
    $this->version = Env::$version;
    Env::init('file', '1.0.0', 'localhost:3306', DB_USER, DB_PASSWORD, DB_NAME);
  }

  function testItCanReturnPluginPrefix() {
    expect(Env::$plugin_prefix)->equals('mailpoet_');
  }

  function testItCanReturnDbPrefix() {
    global $wpdb;
    $db_prefix = $wpdb->prefix . 'mailpoet_';
    expect(Env::$db_prefix)->equals($db_prefix);
  }

  function testItProcessDBHost() {
    Env::init('file', '1.0.0', 'localhost', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('localhost');
    expect(Env::$db_port)->equals('3306');
    expect(Env::$db_source_name)->equals('mysql:host=localhost;port=3306;dbname=db_name;charset='. ENV::$db_charset);

    Env::init('file', '1.0.0', 'localhost:3307', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('localhost');
    expect(Env::$db_port)->equals('3307');
    expect(Env::$db_source_name)->equals('mysql:host=localhost;port=3307;dbname=db_name;charset='. ENV::$db_charset);
  }

  function testItProcessDBHostWithSocket() {
    Env::init('file', '1.0.0', 'localhost:/var/lib/mysql/mysql55.sock', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('localhost');
    expect(Env::$db_socket)->equals('/var/lib/mysql/mysql55.sock');
    expect(Env::$db_source_name)->equals('mysql:host=localhost;port=3306;dbname=db_name;unix_socket=/var/lib/mysql/mysql55.sock;charset='. ENV::$db_charset);
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

  function testItCanGetDbTimezoneOffset() {
    expect(Env::getDbTimezoneOffset('+1.5'))->equals("+01:30");
    expect(Env::getDbTimezoneOffset('+11'))->equals("+11:00");
    expect(Env::getDbTimezoneOffset('-5.5'))->equals("-05:30");
  }

  function _after() {
    // Restore the original environment
    Env::init($this->file, $this->version, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  }
}
