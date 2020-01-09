<?php

namespace MailPoet\Config;

use MailPoet\Services\Bridge;
use MailPoet\Services\Release\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;

class Installer {
  const PREMIUM_PLUGIN_SLUG = 'mailpoet-premium';

  private $slug;

  /** @var SettingsController */
  private $settings;

  public function __construct($slug) {
    $this->slug = $slug;
    $this->settings = SettingsController::getInstance();
  }

  public function init() {
    WPFunctions::get()->addFilter('plugins_api', [$this, 'getPluginInformation'], 10, 3);
  }

  public function getPluginInformation($data, $action = '', $args = null) {
    if ($action === 'plugin_information'
      && isset($args->slug)
      && $args->slug === $this->slug
    ) {
      $data = $this->retrievePluginInformation();
    }

    return $data;
  }

  public static function getPremiumStatus() {
    $slug = self::PREMIUM_PLUGIN_SLUG;

    $premiumPluginActive = License::getLicense();
    $premiumPluginInstalled = $premiumPluginActive || self::isPluginInstalled($slug);
    $premiumPluginInitialized = defined('MAILPOET_PREMIUM_INITIALIZED') && MAILPOET_PREMIUM_INITIALIZED;
    $premiumInstallUrl = $premiumPluginInstalled ? '' : self::getPluginInstallationUrl($slug);
    $premiumActivateUrl = $premiumPluginActive ? '' : self::getPluginActivationUrl($slug);

    return compact(
      'premium_plugin_active',
      'premium_plugin_installed',
      'premium_plugin_initialized',
      'premium_install_url',
      'premium_activate_url'
    );
  }

  public static function isPluginInstalled($slug) {
    $installedPlugin = self::getInstalledPlugin($slug);
    return !empty($installedPlugin);
  }

  public static function getPluginInstallationUrl($slug) {
    $installUrl = WPFunctions::get()->addQueryArg(
      [
        'action'   => 'install-plugin',
        'plugin'   => $slug,
        '_wpnonce' => WPFunctions::get()->wpCreateNonce('install-plugin_' . $slug),
      ],
      WPFunctions::get()->selfAdminUrl('update.php')
    );
    return $installUrl;
  }

  public static function getPluginActivationUrl($slug) {
    $pluginFile = self::getPluginFile($slug);
    if (empty($pluginFile)) {
      return false;
    }
    $activateUrl = WPFunctions::get()->addQueryArg(
      [
        'action'   => 'activate',
        'plugin'   => $pluginFile,
        '_wpnonce' => WPFunctions::get()->wpCreateNonce('activate-plugin_' . $pluginFile),
      ],
      WPFunctions::get()->selfAdminUrl('plugins.php')
    );
    return $activateUrl;
  }

  private static function getInstalledPlugin($slug) {
    $installedPlugin = [];
    if (is_dir(WP_PLUGIN_DIR . '/' . $slug)) {
      $installedPlugin = WPFunctions::get()->getPlugins('/' . $slug);
    }
    return $installedPlugin;
  }

  public static function getPluginFile($slug) {
    $pluginFile = false;
    $installedPlugin = self::getInstalledPlugin($slug);
    if (!empty($installedPlugin)) {
      $pluginFile = $slug . '/' . key($installedPlugin);
    }
    return $pluginFile;
  }

  public function retrievePluginInformation() {
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
