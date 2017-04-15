<?php

namespace MailPoet\Config;

use MailPoet\Util\ProgressBar;

if(!defined('ABSPATH')) exit;

class MP2Migrator {

  private $log_file;
  public $log_file_url;
  public $progressbar;

  public function __construct() {
    $log_filename = Env::$plugin_name . '-mp2migration.log';
    $upload_dir = wp_upload_dir();
    $this->log_file = $upload_dir['basedir'] . '/' . $log_filename;
    $this->log_file_url = $upload_dir['baseurl'] . '/' . $log_filename;
    $this->progressbar = new ProgressBar('mp2migration');
  }

  /**
   * Test if the migration is proposed
   * 
   * @return boolean
   */
  public function proposeMigration() {
    if(isset($_REQUEST['nomigrate'])) {
      // Store the user's choice if he doesn't want to migrate from MP2
      update_option('mailpoet_migration_complete', true);
    }
    if(get_option('mailpoet_migration_complete')) {
      return false;
    } else {
      return $this->tableExists('wysija_campaign'); // Check if the MailPoet 2 tables exist
    }
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
    wp_enqueue_script('jquery-ui-progressbar');
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
    $this->log('START IMPORT');
    update_option('mailpoet_stopImport', false, false); // Reset the stop import action
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

    $this->log('END IMPORT');
  }

  /**
   * Stop the import
   * 
   */
  public function stopImport() {
    update_option('mailpoet_stopImport', true);
    $this->log('IMPORT STOPPED BY USER');
  }

  /**
   * Test if the import must stop
   * 
   * @return boolean Import must stop or not
   */
  private function importStopped() {
    return get_option('mailpoet_stopImport');
  }

}
