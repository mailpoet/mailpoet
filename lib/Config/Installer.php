<?php
namespace MailPoet\Config;

use MailPoet\Services\Bridge;
use MailPoet\Services\Release\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Installer {
  const PREMIUM_PLUGIN_SLUG = 'mailpoet-premium';

  private $slug;

  /** @var SettingsController */
  private $settings;

  function __construct($slug) {
    $this->slug = $slug;
    $this->settings = new SettingsController();
  }

  function init() {
    WPFunctions::get()->addFilter('plugins_api', [$this, 'getPluginInformation'], 10, 3);
  }

  function getPluginInformation($data, $action = '', $args = null) {
    if ($action === 'plugin_information'
      && isset($args->slug)
      && $args->slug === $this->slug
    ) {
      $data = $this->retrievePluginInformation();
    }

    return $data;
  }

  static function getPremiumStatus() {
    $slug = self::PREMIUM_PLUGIN_SLUG;

    $premium_plugin_active = License::getLicense();
    $premium_plugin_installed = $premium_plugin_active || self::isPluginInstalled($slug);
    $premium_install_url = $premium_plugin_installed ? '' : self::getPluginInstallationUrl($slug);
    $premium_activate_url = $premium_plugin_active ? '' : self::getPluginActivationUrl($slug);

    return compact(
      'premium_plugin_active',
      'premium_plugin_installed',
      'premium_install_url',
      'premium_activate_url'
    );
  }

  static function isPluginInstalled($slug) {
    $installed_plugin = self::getInstalledPlugin($slug);
    return !empty($installed_plugin);
  }

  static function getPluginInstallationUrl($slug) {
    $install_url = WPFunctions::get()->addQueryArg(
      [
        'action'   => 'install-plugin',
        'plugin'   => $slug,
        '_wpnonce' => WPFunctions::get()->wpCreateNonce('install-plugin_' . $slug),
      ],
      WPFunctions::get()->selfAdminUrl('update.php')
    );
    return $install_url;
  }

  static function getPluginActivationUrl($slug) {
    $plugin_file = self::getPluginFile($slug);
    if (empty($plugin_file)) {
      return false;
    }
    $activate_url = WPFunctions::get()->addQueryArg(
      [
        'action'   => 'activate',
        'plugin'   => $plugin_file,
        '_wpnonce' => WPFunctions::get()->wpCreateNonce('activate-plugin_' . $plugin_file),
      ],
      WPFunctions::get()->selfAdminUrl('plugins.php')
    );
    return $activate_url;
  }

  private static function getInstalledPlugin($slug) {
    $installed_plugin = [];
    if (is_dir(WP_PLUGIN_DIR . '/' . $slug)) {
      $installed_plugin = WPFunctions::get()->getPlugins('/' . $slug);
    }
    return $installed_plugin;
  }

  static function getPluginFile($slug) {
    $plugin_file = false;
    $installed_plugin = self::getInstalledPlugin($slug);
    if (!empty($installed_plugin)) {
      $plugin_file = $slug . '/' . key($installed_plugin);
    }
    return $plugin_file;
  }

  function retrievePluginInformation() {
    $key = $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
    $api = new API($key);
    $info = $api->getPluginInformation($this->slug);
    $info = $this->formatInformation($info);
    return $info;
  }

  private function formatInformation($info) {
    // cast sections object to array for WP to understand
    if (isset($info->sections)) {
      $info->sections = (array)$info->sections;
    }
    return $info;
  }
}
