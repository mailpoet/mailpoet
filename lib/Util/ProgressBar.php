<?php

namespace MailPoet\Util;

use MailPoet\Config\Env;

if (!defined('ABSPATH')) exit;

if (!class_exists('ProgressBar', false)) {

  /**
   * The Progress Bar class
   *
   */
  class ProgressBar {

    private $total_count = 0;
    private $current_count = 0;
    private $filename;
    public $url;

    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct($progress_bar_id) {
      $filename = $progress_bar_id . '-progress.json';
      $this->filename = Env::$temp_path . '/' . $filename;
      $this->url = Env::$temp_url . '/' . $filename;
      $counters = $this->readProgress();
      if (isset($counters->total)) {
        $this->total_count = $counters->total;
      }
      if (isset($counters->current)) {
        $this->current_count = $counters->current;
      }
    }

    /**
     * Get the progress file URL
     *
     * @return string Progress file URL
     */
    public function getUrl() {
      return $this->url;
    }

    /**
     * Read the progress counters
     *
     * @return array|false Array of counters
     */
    private function readProgress() {
      if (!file_exists($this->filename)) {
        return false;
      }
      $json_content = file_get_contents($this->filename);
      if (is_string($json_content)) {
        return json_decode($json_content);
      }
      return false;
    }

    /**
     * Set the total count
     *
     * @param int $count Count
     */
    public function setTotalCount($count) {
      if (($count != $this->total_count) || ($count == 0)) {
        $this->total_count = $count;
        $this->current_count = 0;
        $this->saveProgress();
      }
    }

    /**
     * Increment the current count
     *
     * @param int $count Count
     */
    public function incrementCurrentCount($count) {
      $this->current_count += $count;
      $this->saveProgress();
    }

    /**
     * Save the progress counters
     *
     */
    private function saveProgress() {
      file_put_contents($this->filename, json_encode([
        'total' => $this->total_count,
        'current' => $this->current_count,
      ]));
    }

    /**
     * Delete the progress file
     *
     */
    public function deleteProgressFile() {
      unlink($this->filename);
    }

  }

}
