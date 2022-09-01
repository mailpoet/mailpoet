<?php

namespace Helper;

use MailPoet\Config\Env;
use MailPoetVendor\Idiorm\ORM;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Database extends \Codeception\Module {
  /**
   * Load a SQL file
   *
   * @param string $filename Filename without extension
   */
  static public function loadSQL($filename) {
    global $wpdb;

    $db = ORM::getDb();
    $fullFilename = Env::$path . '/tests/_data/' . $filename . '.sql';
    $sql = file_get_contents($fullFilename);
    assert(is_string($sql));
    $sql = preg_replace('/`wp_/', '`' . $wpdb->prefix, $sql); // Use the current database prefix
    if (!is_string($sql)) {
      throw new \RuntimeException('Empty or missing ' . $fullFilename);
    }
    $db->exec($sql);
  }
}
