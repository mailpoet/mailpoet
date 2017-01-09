<?php

use MailPoet\Config\Env;

class InitializerTest extends MailPoetTest {
  function testItSetsDBDriverOptions() {
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

  function testItConfiguresHooks() {
    global $wp_filter;
    $is_hooked = false;
    // mailpoet should hook to 'wp_loaded' with priority of 10
    foreach($wp_filter['wp_loaded'][10] as $name => $hook) {
      if(preg_match('/setupHooks/', $name)) $is_hooked = true;
    }
    expect($is_hooked)->true();
  }
}