<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\Config\Env;
use MailPoet\Services\Bridge;
use MailPoet\Services\Release\API;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class Updater {
  private $plugin;
  private $slug;
  private $version;
  public $currentFreeVersion;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    $pluginName,
    $slug,
    $version
  ) {
    $this->plugin = WPFunctions::get()->pluginBasename($pluginName);
    $this->slug = $slug;
    $this->version = $version;
    $this->settings = SettingsController::getInstance();
    $this->currentFreeVersion = MAILPOET_VERSION;
  }

  public function init() {
    WPFunctions::get()->addFilter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);
  }

  public function checkForUpdate($updateTransient) {
    if (!$updateTransient instanceof \stdClass) {
      $updateTransient = new \stdClass;
    }

    $latestVersion = $this->getLatestVersion();

    if (!isset($latestVersion->new_version)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return $updateTransient; // no latest version found.
    }

    if (property_exists($updateTransient, 'response') && isset($updateTransient->response[$this->plugin])) {
      unset($updateTransient->response[$this->plugin]); // remove the cached version from the transient.
    }

    $latestFreeVersion = null;
    if (property_exists($updateTransient, 'response') && isset($updateTransient->response[Env::$pluginPath]->new_version)) {
      $latestFreeVersion = $updateTransient->response[Env::$pluginPath]->new_version;
    }

    if (!$this->shouldShowUpdateNotice($latestVersion->new_version, $latestFreeVersion)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return $updateTransient; // skip update notice.
    }

    if (version_compare((string)$this->version, $latestVersion->new_version, '<')) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $updateTransient->response[$this->plugin] = $latestVersion;
    } else {
      $updateTransient->no_update[$this->plugin] = $latestVersion; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    $updateTransient->last_checked = time(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $updateTransient->checked[$this->plugin] = $this->version;

    return $updateTransient;
  }

  public function getLatestVersion() {
    $key = $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
    $api = new API($key);
    $data = $api->getPluginInformation($this->slug . '/latest');
    return $data;
  }

  public function isVersionCompatible($premiumVersion, $freeVersion): bool {
    if (empty($premiumVersion) || empty($freeVersion)) {
      return false;
    }

    // Extract required main plugin version from premium version
    preg_match('/(\d+\.\d+)\.\d+/i', $premiumVersion, $matches);
    $requiredMainVersion = end($matches);

    // Extract current main plugin minor version
    preg_match('/^\d\.\d+/', $freeVersion, $match);
    $currentMainVersion = !empty($match[0]) ? $match[0] : $freeVersion;

    // Check compatibility
    return version_compare($currentMainVersion, (string)$requiredMainVersion, '>=');
  }

  public function shouldShowUpdateNotice($premiumLatestVersion, $latestFreeVersion = null): bool {
    // first check if the free version in the update transient is compatible with the premium latest version
    if (!empty($latestFreeVersion) && $this->isVersionCompatible($premiumLatestVersion, $latestFreeVersion)) {
      return true;
    }

    // then check if the current free version is compatible with the premium latest version
    return $this->isVersionCompatible($premiumLatestVersion, $this->currentFreeVersion);
  }
}
