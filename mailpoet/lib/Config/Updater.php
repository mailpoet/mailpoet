<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

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

    if (!$this->shouldShowUpdateNotice($latestVersion->new_version)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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

    // Extract major.minor from premium version (e.g., "5.17.0" -> "5.17")
    $premiumParts = explode('.', $premiumVersion);
    $requiredMainVersion = isset($premiumParts[0], $premiumParts[1])
        ? $premiumParts[0] . '.' . $premiumParts[1]
        : $premiumVersion;

    // Extract major.minor from free version (e.g., "5.17.5" -> "5.17")
    $freeParts = explode('.', $freeVersion);
    $currentMainVersion = isset($freeParts[0], $freeParts[1])
        ? $freeParts[0] . '.' . $freeParts[1]
        : $freeVersion;

    // Check compatibility: free version must be >= required version
    return version_compare($currentMainVersion, $requiredMainVersion, '>=');
  }

  public function shouldShowUpdateNotice($premiumLatestVersion): bool {
    // Compare against the free version that's actually installed, not one that's
    // merely available. Since wordpress.org now holds new releases for up to 24h
    // before distributing them (https://wordpress.org/news/2026/06/pts/), the free
    // update can lag behind Premium. Gating on the installed version keeps Premium
    // from jumping ahead and disabling itself.
    return $this->isVersionCompatible($premiumLatestVersion, $this->currentFreeVersion);
  }
}
