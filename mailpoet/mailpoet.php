<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/*
 * Plugin Name: MailPoet
 * Version: 5.7.0
 * Plugin URI: https://www.mailpoet.com
 * Description: Create and send newsletters, post notifications and welcome emails from your WordPress.
 * Author: MailPoet
 * Author URI: https://www.mailpoet.com
 * Requires at least: 6.6
 * Text Domain: mailpoet
 * Domain Path: /lang
 *
 * WC requires at least: 9.5.1
 * WC tested up to: 9.6.0
 *
 * @package WordPress
 * @author MailPoet
 * @since 3.0.0-beta.1
 */

$mailpoetPlugin = [
  'version' => '5.7.0',
  'filename' => __FILE__,
  'path' => dirname(__FILE__),
  'autoloader' => dirname(__FILE__) . '/vendor/autoload.php',
  'initializer' => dirname(__FILE__) . '/mailpoet_initializer.php',
];

const MAILPOET_MINIMUM_REQUIRED_WP_VERSION = '6.6'; // L-1 version, not the latest
const MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION = '9.5'; // L-1 version, not the latest


// Display WP version error notice
function mailpoet_wp_version_notice() {
  $notice = str_replace(
    '[link]',
    '<a href="https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#wp_version" target="_blank">',
    sprintf(
      // translators: %s is the number of minimum WordPress version that MailPoet requires
      __('MailPoet plugin requires WordPress version %s or newer. Please read our [link]instructions[/link] on how to resolve this issue.', 'mailpoet'),
      MAILPOET_MINIMUM_REQUIRED_WP_VERSION
    )
  );
  $notice = str_replace('[/link]', '</a>', $notice);
  printf(
    '<div class="error"><p>%1$s</p></div>',
    wp_kses(
      $notice,
      [
        'a' => [
          'href' => true,
          'target' => true,
        ],
      ]
    )
  );
}

// Display WooCommerce version error notice
function mailpoet_woocommerce_version_notice() {
  $notice = str_replace(
    '[link]',
    '<a href="https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3#woocommerce-version" target="_blank">',
    sprintf(
      // translators: %s is the number of minimum WooCommerce version that MailPoet requires
      __('MailPoet plugin requires WooCommerce version %s or newer. Please update your WooCommerce plugin version, or read our [link]instructions[/link] for additional options on how to resolve this issue.', 'mailpoet'),
      MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION
    )
  );
  $notice = str_replace('[/link]', '</a>', $notice);
  printf(
    '<div class="error"><p>%1$s</p></div>',
    wp_kses(
      $notice,
      [
        'a' => [
          'href' => true,
          'target' => true,
        ],
      ]
    )
  );
}

// Display IIS server error notice
function mailpoet_microsoft_iis_notice() {
  $notice = __("MailPoet plugin cannot run under Microsoft's Internet Information Services (IIS) web server. We recommend that you use a web server powered by Apache or NGINX.", 'mailpoet');
  printf('<div class="error"><p>%1$s</p></div>', esc_html($notice));
}

// Display missing core dependencies error notice
function mailpoet_core_dependency_notice() {
  $notice = __('MailPoet cannot start because it is missing core files. Please reinstall the plugin.', 'mailpoet');
  printf('<div class="error"><p>%1$s</p></div>', esc_html($notice));
}

// Display PHP version error notice
function mailpoet_php_version_notice() {
  $noticeP1 = sprintf(
    // translators: %1$s is the plugin name (MailPoet or MailPoet Premium), %2$s, %3$s, and %4$s are PHP version (e.g. "8.1.30")
    __('%1$s requires PHP version %2$s or newer (%3$s recommended). You are running version %4$s.', 'mailpoet'),
    'MailPoet',
    '7.4',
    '8.1',
    phpversion()
  );

  $noticeP2 = __('Please read our [link]instructions[/link] on how to upgrade your site.', 'mailpoet');
  $noticeP2 = str_replace(
    '[link]',
    '<a href="https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version" target="_blank">',
    $noticeP2
  );
  $noticeP2 = str_replace('[/link]', '</a>', $noticeP2);

  $allowedTags = [
    'a' => [
      'href' => true,
      'target' => true,
    ],
  ];
  printf(
    '<div class="error"><p><strong>%s</strong></p><p>%s</p></div>',
    esc_html($noticeP1),
    wp_kses(
      $noticeP2,
      $allowedTags
    )
  );
}

function mailpoet_check_requirements(array $mailpoetPlugin) {

  // Check for presence of core dependencies
  if (!file_exists($mailpoetPlugin['autoloader']) || !file_exists($mailpoetPlugin['initializer'])) {
    add_action('admin_notices', 'mailpoet_core_dependency_notice');
    return false;
  }

  // Check for Microsoft IIS server
  if (isset($_SERVER['SERVER_SOFTWARE']) && strpos(strtolower(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))), 'microsoft-iis') !== false) {
    add_action('admin_notices', 'mailpoet_microsoft_iis_notice');
    return false;
  }

  // Check for minimum supported WooCommerce version
  if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }
  if (is_plugin_active('woocommerce/woocommerce.php')) {
    $woocommerceVersion = get_plugin_data(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', false, false)['Version'];
    if (version_compare($woocommerceVersion, MAILPOET_MINIMUM_REQUIRED_WOOCOMMERCE_VERSION, '<')) {
      add_action('admin_notices', 'mailpoet_woocommerce_version_notice');
      return false;
    }
  }

  // Check for minimum supported WP version
  if (version_compare(get_bloginfo('version'), MAILPOET_MINIMUM_REQUIRED_WP_VERSION, '<')) {
    add_action('admin_notices', 'mailpoet_wp_version_notice');
    return false;
  }

  // Check for minimum supported PHP version
  if (version_compare(phpversion(), '7.4.0', '<')) {
    add_action('admin_notices', 'mailpoet_php_version_notice');
    return false;
  }

  return true;
}

// Initialize plugin
if (mailpoet_check_requirements($mailpoetPlugin)) {
  require_once($mailpoetPlugin['initializer']);
}
