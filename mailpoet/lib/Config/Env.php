<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class Env {
  const NEWSLETTER_CONTENT_WIDTH = 1320;

  public static $version;
  public static $pluginName;
  public static $pluginPath;
  public static $baseUrl;
  public static $file;
  public static $path;
  public static $viewsPath;
  public static $assetsPath;
  public static $assetsUrl;
  public static $utilPath;
  public static $tempPath;
  public static $cachePath;
  public static $tempUrl;
  public static $languagesPath;
  public static $libPath;
  public static $pluginPrefix;
  /** @var string WP DB prefix + plugin prefix */
  public static $dbPrefix;

  // back compatibility for older Premium plugin with underscore naming
  // (we need to allow it to activate so it can render an update notice)
  public static $plugin_name; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  public static $temp_path; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  public static function init($file, $version) {
    self::$version = $version;
    self::$file = $file;
    self::$path = dirname(self::$file);
    self::$pluginName = 'mailpoet';
    self::$pluginPath = 'mailpoet/mailpoet.php';
    self::$baseUrl = WPFunctions::get()->pluginsUrl('', $file);
    self::$viewsPath = self::$path . '/views';
    self::$assetsPath = self::$path . '/assets';
    self::$assetsUrl = WPFunctions::get()->pluginsUrl('/assets', $file);
    self::$utilPath = self::$path . '/lib/Util';
    $wpUploadDir = WPFunctions::get()->wpUploadDir();
    self::$tempPath = $wpUploadDir['basedir'] . '/' . self::$pluginName;
    self::$cachePath = self::$path . '/generated/twig/';
    self::$tempUrl = $wpUploadDir['baseurl'] . '/' . self::$pluginName;
    self::$languagesPath = self::$path . '/../../languages/plugins/';
    self::$libPath = self::$path . '/lib';
    self::$pluginPrefix = WPFunctions::get()->applyFilters('mailpoet_db_prefix', 'mailpoet_');

    global $wpdb;
    self::$dbPrefix = $wpdb->prefix . self::$pluginPrefix;

    // back compatibility for older Premium plugin with underscore naming
    self::$plugin_name = self::$pluginName; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    self::$temp_path = self::$tempPath; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
