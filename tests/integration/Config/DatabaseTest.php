<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Database;
use MailPoet\Config\Env;
use MailPoetVendor\Idiorm\ORM;

class DatabaseTest extends \MailPoetTest {
  public $database;

  public function __construct() {
    parent::__construct();
    $this->database = new Database();
  }

  public function _before() {
    parent::_before();
    ORM::set_db(null);
  }

  public function testItDefinesTables() {
    expect(defined('MP_SETTINGS_TABLE'))->true();
  }

  public function testItConfiguresLogging() {
    expect(ORM::get_config('logging'))->equals(WP_DEBUG);
  }

  public function testItSetsDBDriverOptions() {
    $this->database->init($this->connection->getWrappedConnection());
    $result = ORM::for_table("")
      ->raw_query(
        'SELECT ' .
        '@@sql_mode as sqlMode, ' .
        '@@session.time_zone as timeZone'
      )
      ->findOne();
    // time zone should be set based on WP's time zone
    expect($result->timeZone)->equals(Env::$dbTimezoneOffset);
  }
}
