<?php
use \UnitTester;
use \MailPoet\Config\Env;

class EnvCest {
  public function _before() {
    global $wpdb;
    $this->db_prefix = $wpdb->prefix;
    Env::init();
  }

  public function itCanReturnTheDbPrefix() {
    expect(Env::$db_prefix)->equals($this->db_prefix);
  }

  public function _after() {
  }
}
