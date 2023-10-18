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
    verify(Env::$pluginPrefix)->equals('mailpoet_');
  }

  public function testItCanReturnDbPrefix() {
    global $wpdb;
    $dbPrefix = $wpdb->prefix . 'mailpoet_';
    verify(Env::$dbPrefix)->equals($dbPrefix);
  }

  public function testItProcessDBHost() {
    Env::init('file', '1.0.0', 'localhost', 'db_user', 'pass123', 'db_name');
    verify(Env::$dbHost)->equals('localhost');
    verify(Env::$dbPort)->null();

    Env::init('file', '1.0.0', 'localhost:3307', 'db_user', 'pass123', 'db_name');
    verify(Env::$dbHost)->equals('localhost');
    verify(Env::$dbPort)->equals('3307');
  }

  public function testItProcessDBHostWithSocket() {
    Env::init('file', '1.0.0', 'localhost:/var/lib/mysql/mysql55.sock', 'db_user', 'pass123', 'db_name');
    verify(Env::$dbHost)->equals('localhost');
    verify(Env::$dbSocket)->equals('/var/lib/mysql/mysql55.sock');
  }

  public function testItProcessDBHostWithIpV6Address() {
    Env::init('file', '1.0.0', '::1', 'db_user', 'pass123', 'db_name');
    verify(Env::$dbHost)->equals('::1');
    verify(Env::$dbSocket)->equals(null);

    Env::init('file', '1.0.0', 'b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036', 'db_user', 'pass123', 'db_name');
    verify(Env::$dbHost)->equals('b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036');
    verify(Env::$dbSocket)->equals(null);
  }

  public function testItCanReturnDbName() {
    verify(Env::$dbName)->equals(DB_NAME);
  }

  public function testItCanReturnDbUser() {
    verify(Env::$dbUsername)->equals(DB_USER);
  }

  public function testItCanReturnDbPassword() {
    verify(Env::$dbPassword)->equals(DB_PASSWORD);
  }

  public function testItCanReturnDbCharset() {
    global $wpdb;
    $charset = $wpdb->charset;
    verify(Env::$dbCharset)->equals($charset);
  }

  public function testItCanReturnDbCollation() {
    global $wpdb;
    $collation = $wpdb->collate;
    verify(Env::$dbCollation)->equals($collation);
  }

  public function testItCanReturnDbCharsetCollate() {
    global $wpdb;
    $charsetCollate = $wpdb->get_charset_collate();
    verify(Env::$dbCharsetCollate)->equals($charsetCollate);
  }

  public function testItCanGetDbTimezoneOffset() {
    verify(Env::getDbTimezoneOffset('+1.5'))->equals("+01:30");
    verify(Env::getDbTimezoneOffset('+11'))->equals("+11:00");
    verify(Env::getDbTimezoneOffset('-5.5'))->equals("-05:30");
  }

  public function _after() {
    parent::_after();
    // Restore the original environment
    Env::init($this->file, $this->version, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
  }
}
