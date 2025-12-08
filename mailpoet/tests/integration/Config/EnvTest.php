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
    Env::init('file', '1.0.0');
  }

  public function testItCanReturnPluginPrefix() {
    verify(Env::$pluginPrefix)->equals('mailpoet_');
  }

  public function testItCanReturnDbPrefix() {
    global $wpdb;
    $dbPrefix = $wpdb->prefix . 'mailpoet_';
    verify(Env::$dbPrefix)->equals($dbPrefix);
  }

  public function testDeprecatedPropertiesExistAsEmptyStrings() {
    // Deprecated properties should exist (to prevent fatal errors) but be empty strings
    verify(Env::$wpDbPrefix)->equals('');
    verify(Env::$dbName)->equals('');
    verify(Env::$dbCharset)->equals('');
    verify(Env::$dbCollation)->equals('');
    verify(Env::$dbCharsetCollate)->equals('');
    verify(Env::$dbHost)->equals('');
    verify(Env::$dbTimezoneOffset)->equals('');
  }

  public function testDeprecatedGetDbTimezoneOffset() {
    verify(Env::getDbTimezoneOffset('+1.5'))->equals('+01:30');
    verify(Env::getDbTimezoneOffset('+11'))->equals('+11:00');
    verify(Env::getDbTimezoneOffset('-5.5'))->equals('-05:30');
    verify(Env::getDbTimezoneOffset('xyz'))->equals('+00:00');
  }

  public function _after() {
    parent::_after();
    // Restore the original environment
    Env::init($this->file, $this->version);
  }
}
