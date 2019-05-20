<?php
namespace MailPoet\Config;

use MailPoet\Services\Bridge;
use MailPoet\Services\Release\API;
use MailPoet\Settings\SettingsController;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class Updater {
  private $plugin;
  private $slug;
  private $version;

  /** @var SettingsController */
  private $settings;

  function __construct($plugin_name, $slug, $version) {
    $this->plugin = WPFunctions::get()->pluginBasename($plugin_name);
    $this->slug = $slug;
    $this->version = $version;
    $this->settings = new SettingsController();
  }

  function init() {
    WPFunctions::get()->addFilter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);
  }

  function checkForUpdate($update_transient) {
    if (!$update_transient instanceof \stdClass) {
      $update_transient = new \stdClass;
    }

    $latest_version = $this->getLatestVersion();

    if (isset($latest_version->new_version)) {
      if (version_compare($this->version, $latest_version->new_version, '<')) {
        $update_transient->response[$this->plugin] = $latest_version;
      }
      $update_transient->last_checked = time();
      $update_transient->checked[$this->plugin] = $this->version;
    }

    return $update_transient;
  }

  function getLatestVersion() {
    $key = $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
    $api = new API($key);
    $data = $api->getPluginInformation($this->slug . '/latest');
    return $data;
  }
}
