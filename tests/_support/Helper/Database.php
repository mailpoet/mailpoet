<?php
namespace Helper;
use MailPoet\Config\Env;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Database extends \Codeception\Module
{
  /**
   * Load a SQL file
   * 
   * @param string $filename Filename without extension
   */
  static public function loadSQL($filename) {
    $db = \ORM::getDb();
    $full_filename = Env::$path . '/tests/_data/' . $filename . '.sql';
    $sql = file_get_contents($full_filename);
    $db->exec($sql);
  }
  
}
