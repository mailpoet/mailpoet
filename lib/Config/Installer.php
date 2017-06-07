<?php
namespace MailPoet\Config;

use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;
use MailPoet\Services\Release\API;
use MailPoet\Util\License\License;

if(!defined('ABSPATH')) exit;

class Installer {
  const PREMIUM_PLUGIN_SLUG = 'mailpoet-premium';

  private $slug;

  function __construct($slug) {
    $this->slug = $slug;
  }

  function init() {
    add_filter('plugins_api', array($this, 'getPluginInformation'), 10, 3);
  }

  function getPluginInformation($data, $action = '', $args = null) {
    if($action === 'plugin_information'
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
    $install_url = add_query_arg(
      array(
        'action'   => 'install-plugin',
        'plugin'   => $slug,
        '_wpnonce' => wp_create_nonce('install-plugin_' . $slug),
      ),
      self_admin_url('update.php')
    );
    return $install_url;
  }

  static function getPluginActivationUrl($slug) {
    $plugin_file = self::getPluginFile($slug);
    if(empty($plugin_file)) {
      return false;
    }
    $activate_url = add_query_arg(
      array(
        'action'   => 'activate',
        'plugin'   => $plugin_file,
        '_wpnonce' => wp_create_nonce('activate-plugin_' . $plugin_file),
      ),
      self_admin_url('plugins.php')
    );
    return $activate_url;
  }

  private static function getInstalledPlugin($slug) {
    $installed_plugin = array();
    if(is_dir(WP_PLUGIN_DIR . '/' . $slug)) {
      $installed_plugin = get_plugins('/' . $slug);
    }
    return $installed_plugin;
  }

  private static function getPluginFile($slug) {
    $plugin_file = false;
    $installed_plugin = self::getInstalledPlugin($slug);
    if(!empty($installed_plugin)) {
      $plugin_file = $slug . '/' . key($installed_plugin);
    }
    return $plugin_file;
  }

  function retrievePluginInformation() {
    $key = Setting::getValue(Bridge::PREMIUM_KEY_SETTING_NAME);
    $api = new API($key);
    $info = $api->getPluginInformation($this->slug);
    $info = $this->formatInformation($info);
    return $info;
  }

  private function formatInformation($info) {
    // cast sections object to array for WP to understand
    if(isset($info->sections)) {
      $info->sections = (array)$info->sections;
    }
    return $info;
  }
}
