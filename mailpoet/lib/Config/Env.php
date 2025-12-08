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
  /**
   * @deprecated Use global $wpdb->prefix instead
   */
  public static $wpDbPrefix = '';
  /**
   * @deprecated Database connection is handled by WordPress $wpdb
   */
  public static $dbHost = '';
  /**
   * @deprecated Database connection is handled by WordPress $wpdb
   */
  public static $dbIsIpv6 = '';
  /**
   * @deprecated Database connection is handled by WordPress $wpdb
   */
  public static $dbSocket = '';
  /**
   * @deprecated Database connection is handled by WordPress $wpdb
   */
  public static $dbPort = '';
  /**
   * @deprecated Use global $wpdb->dbname instead
   */
  public static $dbName = '';
  /**
   * @deprecated Database connection is handled by WordPress $wpdb
   */
  public static $dbUsername = '';
  /**
   * @deprecated Database connection is handled by WordPress $wpdb
   */
  public static $dbPassword = '';
  /**
   * @deprecated Use global $wpdb->charset instead
   */
  public static $dbCharset = '';
  /**
   * @deprecated Use global $wpdb->collate instead
   */
  public static $dbCollation = '';
  /**
   * @deprecated Use global $wpdb->get_charset_collate() instead
   */
  public static $dbCharsetCollate = '';
  /**
   * @deprecated Calculate timezone offset from WordPress gmt_offset option if needed
   */
  public static $dbTimezoneOffset = '';

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

  /**
   * @deprecated Calculate timezone offset from WordPress gmt_offset option directly if needed
   */
  public static function getDbTimezoneOffset($offset = false) {
    $offset = ($offset) ? $offset : WPFunctions::get()->getOption('gmt_offset');
    $offset = (float)($offset);
    $mins = $offset * 60;
    $sgn = ($mins < 0 ? -1 : 1);
    $mins = abs($mins);
    $hrs = floor($mins / 60);
    $mins -= $hrs * 60;
    return sprintf('%+03d:%02d', $hrs * $sgn, $mins);
  }
}
