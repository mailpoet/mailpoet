<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;

class EnvTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    // Back up original environment values
    $this->file = Env::$file;
    $this->version = Env::$version;
    Env::init('file', '1.0.0', 'localhost:3306', DB_USER, DB_PASSWORD, DB_NAME);
  }

  public function testItCanReturnPluginPrefix() {
    expect(Env::$plugin_prefix)->equals('mailpoet_');
  }

  public function testItCanReturnDbPrefix() {
    global $wpdb;
    $db_prefix = $wpdb->prefix . 'mailpoet_';
    expect(Env::$db_prefix)->equals($db_prefix);
  }

  public function testItProcessDBHost() {
    Env::init('file', '1.0.0', 'localhost', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('localhost');
    expect(Env::$db_port)->equals('3306');

    Env::init('file', '1.0.0', 'localhost:3307', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('localhost');
    expect(Env::$db_port)->equals('3307');
  }

  public function testItProcessDBHostWithSocket() {
    Env::init('file', '1.0.0', 'localhost:/var/lib/mysql/mysql55.sock', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('localhost');
    expect(Env::$db_socket)->equals('/var/lib/mysql/mysql55.sock');
  }

  public function testItProcessDBHostWithIpV6Address() {
    Env::init('file', '1.0.0', '::1', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('::1');
    expect(Env::$db_socket)->equals(null);

    Env::init('file', '1.0.0', 'b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036', 'db_user', 'pass123', 'db_name');
    expect(Env::$db_host)->equals('b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036');
    expect(Env::$db_socket)->equals(null);
  }

  public function testItCanReturnDbName() {
    expect(Env::$db_name)->equals(DB_NAME);
  }

  public function testItCanReturnDbUser() {
    expect(Env::$db_username)->equals(DB_USER);
  }

  public function testItCanReturnDbPassword() {
    expect(Env::$db_password)->equals(DB_PASSWORD);
  }

  public function testItCanReturnDbCharset() {
    global $wpdb;
    $charset = $wpdb->charset;
    expect(Env::$db_charset)->equals($charset);
  }

  public function testItCanReturnDbCollation() {
    global $wpdb;
    $collation = $wpdb->collate;
    expect(Env::$db_collation)->equals($collation);
  }

  public function testItCanReturnDbCharsetCollate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    expect(Env::$db_charset_collate)->equals($charset_collate);
  }

  public function testItCanGetDbTimezoneOffset() {
    expect(Env::getDbTimezoneOffset('+1.5'))->equals("+01:30");
    expect(Env::getDbTimezoneOffset('+11'))->equals("+11:00");
    expect(Env::getDbTimezoneOffset('-5.5'))->equals("-05:30");
  }

  public function _after() {
    // Restore the original environment
    Env::init($this->file, $this->version, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  }
}
