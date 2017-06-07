<?php

use MailPoet\Config\Database;
use MailPoet\Config\Env;

class DatabaseTestTest extends MailPoetTest {
  public $database;

  function __construct() {
    parent::__construct();
    $this->database = new Database();
  }

  function _before() {
    \ORM::set_db(null);
  }

  function testItDefinesTables() {
    expect(defined('MP_SETTINGS_TABLE'))->true();
  }

  function testItConfiguresLogging() {
    expect(\ORM::get_config('logging'))->equals(WP_DEBUG);
  }

  function testItSetsUpConnection() {
    expect(\ORM::get_config('username'))->equals(Env::$db_username);
    expect(\ORM::get_config('password'))->equals(Env::$db_password);
  }

  function testItSelectivelyUpdatesDriverTimeoutOption() {
    $database = $this->database;
    $database->setupDriverOptions();
    $current_setting = ORM::for_table("")
      ->raw_query('SELECT @@session.wait_timeout as wait_timeout')
      ->findOne();
    expect($current_setting->wait_timeout)->greaterThan($database->driver_option_wait_timeout);
    $this->_before();
    $database->driver_option_wait_timeout = 99999;
    $database->setupDriverOptions();
    $current_setting = ORM::for_table("")
      ->raw_query('SELECT @@session.wait_timeout as wait_timeout')
      ->findOne();
    expect($current_setting->wait_timeout)->equals(99999);
  }

  function testItSetsDBDriverOptions() {
    $this->database->init();
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