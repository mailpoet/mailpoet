<?php
namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

class RequirementsChecker {
  const TEST_PHP_VERSION = 'PHPVersion';
  const TEST_FOLDER_PERMISSIONS = 'TempAndCacheFolderCreation';
  const TEST_PDO_EXTENSION = 'PDOExtension';
  const TEST_MBSTRING_EXTENSION = 'MbstringExtension';
  public $display_error_notice;

  function __construct($display_error_notice = true) {
    $this->display_error_notice = $display_error_notice;
  }

  function checkAllRequirements() {
    $available_tests = array(
      self::TEST_PDO_EXTENSION,
      self::TEST_PHP_VERSION,
      self::TEST_FOLDER_PERMISSIONS,
      self::TEST_MBSTRING_EXTENSION
    );
    $results = array();
    foreach($available_tests as $test) {
      $results[$test] = call_user_func(array($this, 'check' .  $test));
    }
    return $results;
  }

  function checkPHPVersion() {
    if(version_compare(phpversion(), '5.3.0', '<')) {
      $error = Helpers::replaceLinkTags(
        __('This plugin requires PHP version 5.3 or newer. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
        '//docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_version'
      );
      return $this->processError($error);
    }
    return true;
  }

  function checkTempAndCacheFolderCreation() {
    $paths = array(
      'temp_path' => Env::$temp_path,
      'cache_path' => Env::$cache_path
    );
    if(!is_dir($paths['cache_path']) && !wp_mkdir_p($paths['cache_path'])) {
      $error = Helpers::replaceLinkTags(
        __('This plugin requires write permissions inside the /wp-content/uploads folder. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
        '//docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#folder_permissions'
      );
      return $this->processError($error);
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
      $error = Helpers::replaceLinkTags(
        __('This plugin requires PDO_MYSQL PHP extension. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
        '//docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_extension'
      );
      return $this->processError($error);
    }
    return true;
  }

  function checkMbstringExtension() {
    if(!extension_loaded('mbstring')) {
      require_once Env::$util_path .'/Polyfills.php';
    }
    return true;
  }

  function processError($error) {
    if($this->display_error_notice) {
      WPNotice::displayError($error);
    }
    return false;
  }
}