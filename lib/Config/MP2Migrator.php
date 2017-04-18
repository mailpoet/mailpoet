<?php

namespace MailPoet\Config;

use MailPoet\Util\ProgressBar;

if(!defined('ABSPATH')) exit;

class MP2Migrator {

  private $log_file;
  public $log_file_url;
  public $progressbar;

  public function __construct() {
    $log_filename = 'mp2migration.log';
    $this->log_file = Env::$temp_path . '/' . $log_filename;
    $this->log_file_url = Env::$temp_url . '/' . $log_filename;
    $this->progressbar = new ProgressBar('mp2migration');
  }

  /**
   * Test if the migration is needed
   * 
   * @return boolean
   */
  public function isMigrationNeeded() {
    if(get_option('mailpoet_migration_complete')) {
      return false;
    } else {
      return $this->tableExists('wysija_campaign'); // Check if the MailPoet 2 tables exist
    }
  }

  /**
   * Store the "Skip import" choice
   * 
   */
  public function skipImport() {
    update_option('mailpoet_migration_complete', true);
  }

  /**
   * Test if a table exists
   *
   * @param string $table Table name
   * @return boolean
   */
  private function tableExists($table) {
    global $wpdb;

    try {
      $sql = "SHOW TABLES LIKE '{$wpdb->prefix}{$table}'";
      $result = $wpdb->query($sql);
      return !empty($result);
    } catch (Exception $e) {
      // Do nothing
    }

    return false;
  }

  /**
   * Initialize the migration page
   * 
   */
  public function init() {
    $this->enqueueScripts();
    $this->log('INIT');
  }

  /**
   * Register the JavaScript for the admin area.
   *
   */
  private function enqueueScripts() {
    wp_register_script('mp2migrator', Env::$assets_url . '/js/mp2migrator.js', array('jquery-ui-progressbar'));
    wp_enqueue_script('mp2migrator');
  }

  /**
   * Write a message in the log file
   * 
   * @param string $message
   */
  private function log($message) {
    file_put_contents($this->log_file, "$message\n", FILE_APPEND);
  }

  /**
   * Import the data from MailPoet 2
   * 
   * @return boolean Result
   */
  public function import() {
    ob_start();
    $this->emptyLog();
    $this->log(sprintf("=== START IMPORT %s ===", date('Y-m-d H:i:s')));
    update_option('mailpoet_stopImport', false, false); // Reset the stop import action
    
    $this->displayDataToMigrate();
    
    // TODO to remove, for testing only
    $this->progressbar->setTotalCount(0);
    $this->progressbar->setTotalCount(10);
    for($i = 0; $i < 10; $i++) {
      $this->progressbar->incrementCurrentCount(1);
      usleep(300000);
      if($this->importStopped()) {
        return;
      }
    }

    $this->log(sprintf("=== END IMPORT %s ===", date('Y-m-d H:i:s')));
    $result = ob_get_contents();
    ob_clean();
    return $result;
  }

  /**
   * Empty the log file
   * 
   */
  private function emptyLog() {
    file_put_contents($this->log_file, '');
  }
  
  /**
   * Stop the import
   * 
   */
  public function stopImport() {
    update_option('mailpoet_stopImport', true);
    $this->log(__('IMPORT STOPPED BY USER', Env::$plugin_name));
  }

  /**
   * Test if the import must stop
   * 
   * @return boolean Import must stop or not
   */
  private function importStopped() {
    return get_option('mailpoet_stopImport');
  }

  /**
   * Display the number of data to migrate
   * 
   */
  private function displayDataToMigrate() {
    $data = $this->getDataToMigrate();
    $this->log($data);
  }
  
  /**
   * Get the data to migrate
   * 
   * @return string Data to migrate
   */
  private function getDataToMigrate() {
    $result = '';
    $totalCount = 0;
    
    $this->progressbar->setTotalCount(0);
    
    $result .= __('MailPoet 2 data found:', Env::$plugin_name) . "\n";
    
    // Users
    $usersCount = $this->rowsCount('wysija_user');
    $totalCount += $usersCount;
    $result .= sprintf(_n('%d subscriber', '%d subscribers', $usersCount, Env::$plugin_name), $usersCount) . "\n";
    
    // User Lists
    $usersListsCount = $this->rowsCount('wysija_user_list');
    $totalCount += $usersListsCount;
    $result .= sprintf(_n('%d subscribers list', '%d subscribers lists', $usersListsCount, Env::$plugin_name), $usersListsCount) . "\n";
    
    // Emails
    $emailsCount = $this->rowsCount('wysija_email');
    $totalCount += $emailsCount;
    $result .= sprintf(_n('%d newsletter', '%d newsletters', $emailsCount, Env::$plugin_name), $emailsCount) . "\n";
    
    // Forms
    $formsCount = $this->rowsCount('wysija_form');
    $totalCount += $formsCount;
    $result .= sprintf(_n('%d form', '%d forms', $formsCount, Env::$plugin_name), $formsCount) . "\n";
    
    $this->progressbar->setTotalCount($totalCount);
    
    return $result;
  }
  
  /**
   * Count the number of rows in a table
   * 
   * @param string $table Table
   * @return int Number of rows found
   */
  private function rowsCount($table) {
    global $wpdb;

    $table = $wpdb->prefix . $table;
    $sql = "SELECT COUNT(*) FROM `$table`";
    $count = $wpdb->get_var($sql);
    
    return $count;
  }
  
}
