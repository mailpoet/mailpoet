<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class RequirementsChecker {
  const TEST_FOLDER_PERMISSIONS = 'TempAndCacheFolderCreation';
  const TEST_PDO_EXTENSION = 'PDOExtension';
  const TEST_XML_EXTENSION = 'XmlExtension';
  const TEST_VENDOR_SOURCE = 'VendorSource';

  public $displayErrorNotice;
  public $vendorClasses = [
    '\pQuery',
    '\Cron\CronExpression',
    '\Html2Text\Html2Text',
  ];

  public function __construct($displayErrorNotice = true) {
    $this->displayErrorNotice = $displayErrorNotice;
  }

  public function checkAllRequirements() {
    $availableTests = [
      self::TEST_PDO_EXTENSION,
      self::TEST_FOLDER_PERMISSIONS,
      self::TEST_XML_EXTENSION,
      self::TEST_VENDOR_SOURCE,
    ];
    $results = [];
    foreach ($availableTests as $test) {
      $callback = [$this, 'check' . $test];
      if (is_callable($callback)) {
        $results[$test] = call_user_func($callback);
      }
    }
    return $results;
  }

  public function checkTempAndCacheFolderCreation() {
    $paths = [
      'temp_path' => Env::$tempPath,
      'cache_path' => Env::$cachePath,
    ];
    if (!is_dir($paths['cache_path']) && !wp_mkdir_p($paths['cache_path'])) {
      $error = Helpers::replaceLinkTags(
        WPFunctions::get()->__('MailPoet requires write permissions inside the /wp-content/uploads folder. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
        'https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#folder_permissions',
        ['target' => '_blank']
      );
      return $this->processError($error);
    }
    foreach ($paths as $path) {
      $indexFile = $path . '/index.php';
      if (!file_exists($indexFile)) {
        file_put_contents(
          $path . '/index.php',
          str_replace('\n', PHP_EOL, '<?php\n\n// Silence is golden')
        );
      }
    }
    return true;
  }

  public function checkPDOExtension() {
    if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) return true;
    $error = Helpers::replaceLinkTags(
      WPFunctions::get()->__('MailPoet requires a PDO_MYSQL PHP extension. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
      'https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_extension',
      ['target' => '_blank']
    );
    return $this->processError($error);
  }

  public function checkXmlExtension() {
    if (extension_loaded('xml')) return true;
    $error = Helpers::replaceLinkTags(
      WPFunctions::get()->__('MailPoet requires an XML PHP extension. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
      'https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#php_extension',
      ['target' => '_blank']
    );
    return $this->processError($error);
  }

  public function checkVendorSource() {
    foreach ($this->vendorClasses as $dependency) {
      $dependencyPath = $this->getDependencyPath($dependency);
      if (!$dependencyPath) {
        $error = sprintf(
          WPFunctions::get()->__('A MailPoet dependency (%s) does not appear to be loaded correctly, thus MailPoet will not work correctly. Please reinstall the plugin.', 'mailpoet'),
          $dependency
        );

        return $this->processError($error);
      }

      $pattern = '#' . preg_quote(Env::$path) . '[\\\/]#';
      $isLoadedByPlugin = preg_match($pattern, $dependencyPath);
      if (!$isLoadedByPlugin) {
        $error = sprintf(
          WPFunctions::get()->__('MailPoet has detected a dependency conflict (%s) with another plugin (%s), which may cause unexpected behavior. Please disable the offending plugin to fix this issue.', 'mailpoet'),
          $dependency,
          $dependencyPath
        );

        return $this->processError($error);
      }
    }

    return true;
  }

  private function getDependencyPath($namespacedClass) {
    try {
      $reflector = new \ReflectionClass($namespacedClass);
      return $reflector->getFileName();
    } catch (\ReflectionException $ex) {
      return false;
    }
  }

  public function processError($error) {
    if ($this->displayErrorNotice) {
      WPNotice::displayError($error);
    }
    return false;
  }
}
