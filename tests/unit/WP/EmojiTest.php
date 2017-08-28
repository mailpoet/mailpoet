<?php
namespace MailPoet\Test\WP;

use MailPoet\Config\Env;
use MailPoet\WP\Emoji;

class EmojiTest extends \MailPoetTest {
  function _before() {
    $this->data_encoded = "Emojis: &#x1f603;&#x1f635;&#x1f4aa;, not emojis: &#046;&#0142;";
    $this->data_decoded = "Emojis: 😃😵💪, not emojis: &#046;&#0142;";

    $this->column = 'dummycol';
  }

  function testItCanEncodeForUTF8Column() {
    $table = Env::$db_prefix . 'dummytable_utf8';
    $this->createTable($table, 'utf8');

    $result = Emoji::encodeForUTF8Column($table, $this->column, $this->data_decoded);
    expect($result)->equals($this->data_encoded);

    $this->dropTable($table);
  }

  function testItDoesNotEncodeForUTF8MB4Column() {
    $table = Env::$db_prefix . 'dummytable_utf8mb4';
    $this->createTable($table, 'utf8mb4');

    $result = Emoji::encodeForUTF8Column($table, $this->column, $this->data_decoded);
    expect($result)->equals($this->data_decoded);

    $this->dropTable($table);
  }

  function testItCanDecodeEntities() {
    $result = Emoji::decodeEntities($this->data_encoded);
    expect($result)->equals($this->data_decoded);
  }

  private function createTable($table, $charset) {
    \ORM::raw_execute(
      'CREATE TABLE IF NOT EXISTS ' . $table
      . ' (' . $this->column . ' TEXT) '
      . 'DEFAULT CHARSET=' . $charset . ';'
    );
  }

  private function dropTable($table) {
    \ORM::raw_execute('DROP TABLE IF EXISTS ' . $table);
  }
}
