<?php
namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WP\Notice as WPNotice;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class RequirementsChecker {
  const TEST_FOLDER_PERMISSIONS = 'TempAndCacheFolderCreation';
  const TEST_PDO_EXTENSION = 'PDOExtension';
  const TEST_MBSTRING_EXTENSION = 'MbstringExtension';
  const TEST_XML_EXTENSION = 'XmlExtension';
  const TEST_VENDOR_SOURCE = 'VendorSource';
  const TWIG_SUPPORTED_VERSIONS = '1.26.0-1.34.4';

  public $display_error_notice;
  public $vendor_classes = array(
    '\ORM',
    '\Model',
    '\Twig_Environment',
    '\Twig_Loader_Filesystem',
    '\Twig_Lexer',
    '\Twig_Extension',
    '\Twig_SimpleFunction',
    '\Swift_Mailer',
    '\Swift_SmtpTransport',
    '\Swift_Message',
    '\Carbon\Carbon',
    '\Sudzy\ValidModel',
    '\Sudzy\ValidationException',
    '\Sudzy\Engine',
    '\pQuery',
    '\Cron\CronExpression',
    '\Html2Text\Html2Text',
    '\csstidy',
  );

  function __construct($display_error_notice = true) {
    $this->display_error_notice = $display_error_notice;
  }

  function checkAllRequirements() {
    $available_tests = array(
      self::TEST_PDO_EXTENSION,
      self::TEST_FOLDER_PERMISSIONS,
      self::TEST_MBSTRING_EXTENSION,
      self::TEST_XML_EXTENSION,
      self::TEST_VENDOR_SOURCE
    );
    $results = array();
    foreach ($available_tests as $test) {
      $results[$test] = call_user_func(array($this, 'check' .  $test));
    }
    return $results;
  }

  function checkTempAndCacheFolderCreation() {
    $paths = array(
      'temp_path' => Env::$temp_path,
      'cache_path' => Env::$cache_path
    );
    if (!is_dir($paths['cache_path']) && !wp_mkdir_p($paths['cache_path'])) {
      $error = Helpers::replaceLinkTags(
        WPFunctions::get()->__('MailPoet requires write permissions inside the /wp-content/uploads folder. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
        '//beta.docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#folder_permissions',
        array('target' => '_blank')
      );
      return $this->processError($error);
    }
    foreach ($paths as $path) {
      $index_file = $path . '/index.php';
      if (!file_exists($index_file)) {
        file_put_contents(
          $path . '/index.php',
          str_replace('\n', PHP_EOL, '<?php\n\n// Silence is golden')
        );
      }
    }
    return true;
  }

  function checkPDOExtension() {
    if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) return true;
    $error = Helpers::replaceLinkTags(
      WPFunctions::get()->__('MailPoet requires a PDO_MYSQL PHP extension. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
      '//beta.docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_extension',
      array('target' => '_blank')
    );
    return $this->processError($error);
  }

  function checkMbstringExtension() {
    if (!extension_loaded('mbstring')) {
      require_once Env::$util_path .'/Polyfills.php';
    }
    return true;
  }

  function checkXmlExtension() {
    if (extension_loaded('xml')) return true;
    $error = Helpers::replaceLinkTags(
      WPFunctions::get()->__('MailPoet requires an XML PHP extension. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
      '//beta.docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_extension',
      array('target' => '_blank')
    );
    return $this->processError($error);
  }

  function checkVendorSource() {
    foreach ($this->vendor_classes as $dependency) {
      $dependency_path = $this->getDependencyPath($dependency);
      if (!$dependency_path) {
        $error = sprintf(
          WPFunctions::get()->__('A MailPoet dependency (%s) does not appear to be loaded correctly, thus MailPoet will not work correctly. Please reinstall the plugin.', 'mailpoet'),
          $dependency
        );

        return $this->processError($error);
      }

      $pattern = '#' . preg_quote(Env::$path) . '[\\\/]#';
      $is_loaded_by_plugin = preg_match($pattern, $dependency_path);
      if (!$is_loaded_by_plugin) {
        $error = sprintf(
          WPFunctions::get()->__('MailPoet has detected a dependency conflict (%s) with another plugin (%s), which may cause unexpected behavior. Please disable the offending plugin to fix this issue.', 'mailpoet'),
          $dependency,
          $dependency_path
        );

        $return_error = true;

        // if a Twig dependency is loaded by another plugin, check for valid version
        if (strpos($dependency, '\Twig_') === 0) {
          $return_error = ($this->isValidTwigVersion()) ? false : $return_error;
        }

        if ($return_error) return $this->processError($error);
      }
    }

    return true;
  }

  function isValidTwigVersion() {
    list($minimum_version, $maximum_version) = explode('-', self::TWIG_SUPPORTED_VERSIONS);
    return (
      class_exists('\Twig_Environment') &&
      defined('\Twig_Environment::VERSION') &&
      version_compare(\Twig_Environment::VERSION, $minimum_version, '>=') &&
      version_compare(\Twig_Environment::VERSION, $maximum_version, '<=')
    );
  }

  private function getDependencyPath($namespaced_class) {
    try {
      $reflector = new \ReflectionClass($namespaced_class);
      return $reflector->getFileName();
    } catch (\ReflectionException $ex) {
      return false;
    }
  }

  function processError($error) {
    if ($this->display_error_notice) {
      WPNotice::displayError($error);
    }
    return false;
  }
}
