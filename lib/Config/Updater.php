<?php
namespace MailPoet\Config;

use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;
use MailPoet\Services\Release\API;

if(!defined('ABSPATH')) exit;

class Updater {
  private $plugin;
  private $slug;
  private $version;

  function __construct($plugin_name, $slug, $version) {
    $this->plugin = plugin_basename($plugin_name);
    $this->slug = $slug;
    $this->version = $version;
  }

  function init() {
    add_filter('pre_set_site_transient_update_plugins', array($this, 'checkForUpdate'));
  }

  function checkForUpdate($update_transient) {
    if(!is_object($update_transient)) {
      $update_transient = new \stdClass;
    }

    $latest_version = $this->getLatestVersion();

    if(isset($latest_version->new_version)) {
      if(version_compare($this->version, $latest_version->new_version, '<')) {
        $update_transient->response[$this->plugin] = $latest_version;
      }
      $update_transient->last_checked = time();
      $update_transient->checked[$this->plugin] = $this->version;
    }

    return $update_transient;
  }

  function getLatestVersion() {
    $key = Setting::getValue(Bridge::PREMIUM_KEY_SETTING_NAME);
    $api = new API($key);
    $data = $api->getPluginInformation($this->slug . '/latest');
    return $data;
  }
}
