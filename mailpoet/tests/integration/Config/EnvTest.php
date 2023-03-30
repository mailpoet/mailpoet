<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;

class EnvTest extends \MailPoetTest {
  public $version;
  public $file;

  public function _before() {
    parent::_before();
    // Back up original environment values
    $this->file = Env::$file;
    $this->version = Env::$version;
    Env::init('file', '1.0.0', 'localhost:3306', DB_USER, DB_PASSWORD, DB_NAME);
  }

  public function testItCanReturnPluginPrefix() {
    expect(Env::$pluginPrefix)->equals('mailpoet_');
  }

  public function testItCanReturnDbPrefix() {
    global $wpdb;
    $dbPrefix = $wpdb->prefix . 'mailpoet_';
    expect(Env::$dbPrefix)->equals($dbPrefix);
  }

  public function testItProcessDBHost() {
    Env::init('file', '1.0.0', 'localhost', 'db_user', 'pass123', 'db_name');
    expect(Env::$dbHost)->equals('localhost');
    expect(Env::$dbPort)->null();

    Env::init('file', '1.0.0', 'localhost:3307', 'db_user', 'pass123', 'db_name');
    expect(Env::$dbHost)->equals('localhost');
    expect(Env::$dbPort)->equals('3307');
  }

  public function testItProcessDBHostWithSocket() {
    Env::init('file', '1.0.0', 'localhost:/var/lib/mysql/mysql55.sock', 'db_user', 'pass123', 'db_name');
    expect(Env::$dbHost)->equals('localhost');
    expect(Env::$dbSocket)->equals('/var/lib/mysql/mysql55.sock');
  }

  public function testItProcessDBHostWithIpV6Address() {
    Env::init('file', '1.0.0', '::1', 'db_user', 'pass123', 'db_name');
    expect(Env::$dbHost)->equals('::1');
    expect(Env::$dbSocket)->equals(null);

    Env::init('file', '1.0.0', 'b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036', 'db_user', 'pass123', 'db_name');
    expect(Env::$dbHost)->equals('b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036');
    expect(Env::$dbSocket)->equals(null);
  }

  public function testItCanReturnDbName() {
    expect(Env::$dbName)->equals(DB_NAME);
  }

  public function testItCanReturnDbUser() {
    expect(Env::$dbUsername)->equals(DB_USER);
  }

  public function testItCanReturnDbPassword() {
    expect(Env::$dbPassword)->equals(DB_PASSWORD);
  }

  public function testItCanReturnDbCharset() {
    global $wpdb;
    $charset = $wpdb->charset;
    expect(Env::$dbCharset)->equals($charset);
  }

  public function testItCanReturnDbCollation() {
    global $wpdb;
    $collation = $wpdb->collate;
    expect(Env::$dbCollation)->equals($collation);
  }

  public function testItCanReturnDbCharsetCollate() {
    global $wpdb;
    $charsetCollate = $wpdb->get_charset_collate();
    expect(Env::$dbCharsetCollate)->equals($charsetCollate);
  }

  public function testItCanGetDbTimezoneOffset() {
    expect(Env::getDbTimezoneOffset('+1.5'))->equals("+01:30");
    expect(Env::getDbTimezoneOffset('+11'))->equals("+11:00");
    expect(Env::getDbTimezoneOffset('-5.5'))->equals("-05:30");
  }

  public function _after() {
    parent::_after();
    // Restore the original environment
    Env::init($this->file, $this->version, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  }
}
