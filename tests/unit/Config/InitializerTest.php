<?php

use MailPoet\Config\Env;

class InitializerTest extends MailPoetTest {
  function testItSetsDBDriverOptions() {
    $result = ORM::for_table("")
      ->raw_query('SELECT ' .
                  '@@sql_mode as sql_mode, ' .
                  '@@session.time_zone as time_zone, ' .
                  '@@session.character_set_client as client_character, ' .
                  '@@session.character_set_results as results_character, ' .
                  '@@session.character_set_connection as connection_character')
      ->findOne();
    // disable ONLY_FULL_GROUP_BY
    expect($result->sql_mode)->notContains('ONLY_FULL_GROUP_BY');
    // time zone should be set based on WP's time zone
    expect($result->time_zone)->equals(Env::$db_timezone_offset);
    // character set should be set to utf8
    expect($result->client_character)->equals('utf8');
    expect($result->results_character)->equals('utf8');
    expect($result->connection_character)->equals('utf8');
  }
}