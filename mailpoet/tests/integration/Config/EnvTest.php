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

  public function _after() {
    parent::_after();
    // Restore the original environment
    Env::init($this->file, $this->version);
  }
}
