<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Database;
use MailPoet\Config\Env;
use MailPoetVendor\Idiorm\ORM;

class DatabaseTest extends \MailPoetTest {
  public $database;

  function __construct() {
    parent::__construct();
    $this->database = new Database();
  }

  function _before() {
    parent::_before();
    ORM::set_db(null);
  }

  function testItDefinesTables() {
    expect(defined('MP_SETTINGS_TABLE'))->true();
  }

  function testItConfiguresLogging() {
    expect(ORM::get_config('logging'))->equals(WP_DEBUG);
  }

  function testItSetsDBDriverOptions() {
    $this->database->init($this->connection->getWrappedConnection());
    $result = ORM::for_table("")
      ->raw_query(
        'SELECT ' .
        '@@sql_mode as sql_mode, ' .
        '@@session.time_zone as time_zone'
      )
      ->findOne();
    // disable ONLY_FULL_GROUP_BY
    expect($result->sql_mode)->notContains('ONLY_FULL_GROUP_BY');
    // time zone should be set based on WP's time zone
    expect($result->time_zone)->equals(Env::$db_timezone_offset);
  }
}
