<?php
namespace MailPoet\Config;

use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

class RequirementsChecker {
  function check() {
    $available_tests = array(
      'PHPVersion',
      'WritableTempAndCacheFolders',
      'PDOExtension',
      'MbstringExtension'
    );
    $test_results = array();
    foreach($available_tests as $test) {
      $test_results[$test] = call_user_func(array($this, 'check' .  $test));
    }
    return $test_results;
  }

  function checkPHPVersion() {
    if(version_compare(phpversion(), '5.3.0', '<')) {
      return $this->displayError(
        __('This plugin requires PHP version 5.3 or newer.', 'mailpoet')
      );
    }
    return true;
  }

  function checkWritableTempAndCacheFolders() {
    $paths = array(
      'temp_path' => Env::$temp_path,
      'cache_path' => Env::$cache_path
    );
    if(!is_dir($paths['cache_path']) && !wp_mkdir_p($paths['cache_path'])) {
      return $this->displayError(
        __('This plugin requires read/write permissions inside a WordPress uploads folder.', 'mailpoet')
      );
    }
    foreach($paths as $path) {
      $index_file = $path . '/index.php';
      if(!file_exists($index_file)) {
        file_put_contents(
          $path . '/index.php',
          str_replace('\n', PHP_EOL, '<?php\n\n// Silence is golden')
        );
      }
    }
    return true;
  }

  function checkPDOExtension() {
    if(!extension_loaded('pdo') && !extension_loaded('pdo_mysql')) {
      $this->displayError(
        __('This plugin requires PDO and PDO_MYSQL PHP extensions.', 'mailpoet')
      );
    }
    return true;
  }

  function checkMbstringExtension() {
    if(!extension_loaded('mbstring')) {
      return $this->displayError(
        __('This plugin requires mbstring PHP extension.', 'mailpoet')
      );
    }
    return true;
  }

  function displayError($error) {
    WPNotice::displayError($error);
    return false;
  }
}